<?php

class DigiGold
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Tehran');
        set_time_limit(120);
        $this->mkdir('search');
        $this->mkdir('product');
        $this->mkdir('analyze');
    }

    function date()
    {
        return date('Y-m-d-H');
    }

    function path($dir, $fileName = null)
    {
        $array = ['.', 'cache', $dir, $this->date()];
        if ($fileName) {
            $array[] = $fileName;
        }
        return implode(DIRECTORY_SEPARATOR, $array);
    }

    function mkdir($dir)
    {
        $path = $this->path($dir);
        file_exists($path) || mkdir($path, 0777, true);
        return $path;
    }

    function sendMultiGet(array $urls, $fn, $chunkLength = 60)
    {
        foreach (array_chunk($urls, $chunkLength) as $chunkedUrls) {

            $multiCurl = curl_multi_init();

            foreach ($chunkedUrls as $url) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "accept: application/json, text/plain, */*",
                    "accept-language: en-US,en;q=0.9,fa;q=0.8",
                    "priority: u=1, i",
                    "sec-fetch-dest: empty",
                    "sec-fetch-mode: cors",
                    "sec-fetch-site: same-site",
                    "x-web-client: desktop",
                    "x-web-client-id: web",
                    "x-web-optimize-response: 1"
                ]);
                curl_multi_add_handle($multiCurl, $ch);
            }

            do {
                curl_multi_exec($multiCurl, $running);
            } while ($running > 0);

            while (($info = curl_multi_info_read($multiCurl)) !== false) {
                $ch = $info['handle'];
                $response = curl_multi_getcontent($ch);
                $fn($response);
                curl_multi_remove_handle($multiCurl, $ch);
            }
        }
    }

    function readJson($path): array
    {
        return (array)json_decode(file_get_contents($path), true);
    }

    function writeJson($path, ?array $arrayContent)
    {
        file_put_contents($path, json_encode((array)$arrayContent, JSON_UNESCAPED_UNICODE));
    }

    function searchList($fromPage, $toPage)
    {
        $urls = [];
        for ($i = $fromPage; $i <= $toPage; $i++) {
            $path = $this->path('search', $i . '.json');
            if (! file_exists($path)) {
                $urls[] = 'https://api.digikala.com/v1/categories/bullion/search/?has_selling_stock=1&page=' . $i . '&sort=7';
            }
        }
        if (empty($urls)) {
            return;
        }
        $this->sendMultiGet($urls, function ($response) {
            $response = json_decode($response, true);
            if (! empty($response['data']['pager']['current_page'])) {
                $path = $this->path('search', $response['data']['pager']['current_page'] . '.json');
                $this->writeJson($path, $response);
            }
        });
    }

    function search()
    {
        $this->searchList(1, 1);
        $path = $this->path('search', 1 . '.json');
        if (
            file_exists($path) &&
            ($response = $this->readJson($path)) &&
            (isset($response['data']['pager']['total_pages'])) && ($response['data']['pager']['total_pages'] > 1)
        ) {
            $this->searchList(2, $response['data']['pager']['total_pages']);
        }
    }

    function product()
    {
        $urls = [];
        $searchPaths = glob($this->path('search', '*.json'));
        natcasesort($searchPaths);
        foreach ($searchPaths as $searchPath) {
            $page = basename($searchPath, '.json');
            $pageContent = $this->readJson($searchPath);
            if (empty($pageContent['data']['products'])) {
                continue;
            }
            foreach ($pageContent['data']['products'] as $product) {
                $path = $this->path('product', $product['id'] . '.json');
                if (! file_exists($path)) {
                    $urls[] = 'https://api.digikala.com/v2/product/' . $product['id'] . '/';
                }
            }
        }

        $this->sendMultiGet($urls, function ($response) {
            $response = json_decode($response, true);
            if (! empty($response['data']['product']['id'])) {
                $path = $this->path('product', $response['data']['product']['id'] . '.json');
                $this->writeJson($path, $response);
            }
        });
    }

    function analyze()
    {
        $path = $this->path('analyze', 'analyze.json');
        if (file_exists($path)) {
            return $path;
        }

        $variantsAyar = [];
        $sellers = [];
        $sizes = [];
        $sellingPrices = ['min' => null, 'max' => null];

        $productPaths = glob($this->path('product', '*.json'));
        foreach ($productPaths as $productPath) {
            try {
                $data = $this->readJson($productPath)['data'];
                if (empty($data['product']['variants'])) {
                    continue;
                }
                $productId = $data['product']['id'];
                $productTitleFa = $data['product']['title_fa'];
                $ayar = $this->extractAyar($productTitleFa);
                foreach ($data['product']['variants'] as $variant) {
                    $sellerId = $variant['seller']['id'];
                    $sellerTitle = $variant['seller']['title'];
                    $size = floatval($variant['price']['gold_price_details']['size']);
                    $sellingPrice = floatval($variant['price']['selling_price']);
                    $pricePerGram = ($sellingPrice / $size);
                    //
                    $variantsAyar[$ayar][] = [
                        'id' => $productId,
                        'title_fa' => $productTitleFa,
                        'selling_price' => $sellingPrice,
                        'seller_id' => $sellerId,
                        'seller_title' => $sellerTitle,
                        'size' => $size,
                        'url' => $data['seo']['open_graph']['url'],
                        'image' => $data['seo']['open_graph']['image'],
                        '_ayar' => $ayar,
                        '_selling_price_formatted' => number_format($sellingPrice / 10),
                        '_price_per_gram' => $pricePerGram,
                        '_price_per_gram_formatted' => number_format($pricePerGram / 10),
                    ];
                    //
                    $sellers[$sellerId] = [
                        'id' => $sellerId,
                        'title' => $sellerTitle,
                        'count' => (isset($sellers[$sellerId]) ? $sellers[$sellerId]['count'] + 1 : 1),
                    ];
                    //
                    $sizes[strval($size)] = $size;
                    //
                    if ($sellingPrices['min'] === null || $sellingPrice < $sellingPrices['min']) {
                        $sellingPrices['min'] = $sellingPrice;
                    }
                    if ($sellingPrices['max'] === null || $sellingPrices['max'] < $sellingPrice) {
                        $sellingPrices['max'] = $sellingPrice;
                    }
                }
            } catch (\Throwable $th) {
            } catch (\Exception $e) {
            }
        }

        $sizes = array_values($sizes);
        sort($sizes);
        //
        usort($sellers, function ($a, $b) {
            return $b['count'] - $a['count'];
        });
        //
        ksort($variantsAyar);
        foreach ($variantsAyar as $ayar => $variants) {
            usort($variants, function ($a, $b) {
                return $a['_price_per_gram'] - $b['_price_per_gram'];
            });
            $variantsAyar[$ayar] = $variants;
        }

        $this->writeJson($path, [
            'date' => $this->date(),
            'selling_prices' => $sellingPrices,
            'sizes' => $sizes,
            'sellers' => $sellers,
            'variants_ayar' => $variantsAyar,
        ]);

        return $path;
    }

    function extractAyar($productName)
    {
        foreach (
            [
                '18عیار' => 18,
                '18 عیار' => 18,
                //
                '24 عیار' => 24,
                '۲۴ عیار' => 24,
                '24 عیـار' => 24,
                //
                '750 عیار' => 750,
                '995 عیار' => 995,
                '999.9 عیار' => 999,
                //
                'شمش طلا 24 ' => 24,
            ] as $ayarKey => $ayarValue
        ) {
            if (stripos($productName, $ayarKey) !== false) {
                return $ayarValue;
            }
        }

        return 0;
    }

    function getLastAnalyze()
    {
        $this->search();
        $this->product();
        $path = $this->analyze();

        return $this->readJson($path);
    }
}
