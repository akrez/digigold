<?php

class DigiGold
{
    public function __construct(public $basePath)
    {
        foreach (['search', 'product'] as $dir) {
            $path = $this->path($dir);
            file_exists($path) || mkdir($path, 0777, true);
        }
    }

    protected function path($dir, $fileName = null)
    {
        $array = [$this->basePath, $dir];
        if ($fileName !== null) {
            $array[] = $fileName;
        }

        return implode(DIRECTORY_SEPARATOR, $array);
    }

    protected function sendMultiGet(array $urls, $fn, $chunkLength = 60)
    {
        foreach (array_chunk($urls, $chunkLength) as $chunkedUrls) {

            $multiCurl = curl_multi_init();

            foreach ($chunkedUrls as $url) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'accept: application/json, text/plain, */*',
                    'accept-language: en-US,en;q=0.9,fa;q=0.8',
                    'priority: u=1, i',
                    'sec-fetch-dest: empty',
                    'sec-fetch-mode: cors',
                    'sec-fetch-site: same-site',
                    'x-web-client: desktop',
                    'x-web-client-id: web',
                    'x-web-optimize-response: 1',
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

    protected function sanitizeNumber($string)
    {
        return preg_replace('/[^\\d.]+/', '', $string);
    }

    protected function readJson($path): array
    {
        return (array) json_decode(file_get_contents($path), true);
    }

    protected function writeJson($path, ?array $arrayContent)
    {
        file_put_contents($path, json_encode((array) $arrayContent, JSON_UNESCAPED_UNICODE));
    }

    protected function downloadSearchPages($fromPage, $toPage)
    {
        $urls = [];
        for ($i = $fromPage; $i <= $toPage; $i++) {
            $path = $this->path('search', $i.'.json');
            if (! file_exists($path)) {
                $urls[] = 'https://api.digikala.com/v1/categories/bullion/search/?has_selling_stock=1&page='.$i.'&sort=7';
            }
        }
        if (empty($urls)) {
            return;
        }
        $this->sendMultiGet($urls, function ($response) {
            $response = json_decode($response, true);
            if (! empty($response['data']['pager']['current_page'])) {
                $path = $this->path('search', $response['data']['pager']['current_page'].'.json');
                $this->writeJson($path, $response);
            }
        });
    }

    protected function downloadProductPages()
    {
        $urls = [];
        $searchPaths = glob($this->path('search', '*.json'));
        natcasesort($searchPaths);
        foreach ($searchPaths as $searchPath) {
            $pageContent = $this->readJson($searchPath);
            if (empty($pageContent['data']['products'])) {
                continue;
            }
            foreach ($pageContent['data']['products'] as $product) {
                $path = $this->path('product', $product['id'].'.json');
                if (! file_exists($path)) {
                    $urls[] = 'https://api.digikala.com/v2/product/'.$product['id'].'/';
                }
            }
        }

        $this->sendMultiGet($urls, function ($response) {
            $response = json_decode($response, true);
            if (! empty($response['data']['product']['id'])) {
                $path = $this->path('product', $response['data']['product']['id'].'.json');
                $this->writeJson($path, $response);
            }
        });
    }

    protected function writeAnalyzeFile($path)
    {
        $variantsCarat = [];
        $sellers = [];
        $sellingPrices = [];
        $sizes = [];

        $productPaths = glob($this->path('product', '*.json'));
        foreach ($productPaths as $productPath) {
            try {
                $data = $this->readJson($productPath)['data'];
                if (empty($data['product']['variants'])) {
                    continue;
                }
                $productId = $data['product']['id'];
                $productTitleFa = $data['product']['title_fa'];
                $carat = $this->extractCarat($data['product']);
                foreach ($data['product']['variants'] as $variant) {
                    $sellerId = $variant['seller']['id'];
                    $sellerTitle = $variant['seller']['title'];
                    $size = $this->extractSize($variant);
                    $sellingPrice = floatval($variant['price']['selling_price']);
                    $pricePerGram = ($sellingPrice / $size);
                    //
                    $variantsCarat[strval($carat)][] = [
                        'id' => $productId,
                        'title_fa' => $productTitleFa,
                        'selling_price' => $sellingPrice,
                        'seller_id' => $sellerId,
                        'seller_title' => $sellerTitle,
                        'size' => $size,
                        'url' => $data['seo']['open_graph']['url'],
                        'image' => $data['seo']['open_graph']['image'],
                        '_carat' => $carat,
                        '_selling_price_formatted' => number_format($sellingPrice / 10),
                        '_price_per_gram' => $pricePerGram,
                        '_price_per_gram_formatted' => number_format($pricePerGram / 10),
                        'source' => 'Digikala',
                    ];
                    //
                    $sellers[$sellerId] = [
                        'id' => $sellerId,
                        'title' => $sellerTitle,
                        'count' => (isset($sellers[$sellerId]) ? $sellers[$sellerId]['count'] + 1 : 1),
                    ];
                    //
                    $sizes[strval($size)] = $size;
                    $sellingPrices[strval($sellingPrice)] = $sellingPrice;
                }
            } catch (\Throwable $th) {
            } catch (\Exception $e) {
            }
        }

        usort($sellers, function ($a, $b) {
            return $b['count'] - $a['count'];
        });
        //
        ksort($variantsCarat);
        foreach ($variantsCarat as $carat => $variants) {
            usort($variants, function ($a, $b) {
                return $a['_price_per_gram'] - $b['_price_per_gram'];
            });
            $variantsCarat[$carat] = $variants;
        }

        $this->writeJson($path, [
            'date' => date('Y-m-d H:i:s'),
            'sizes' => ['min' => min($sizes), 'max' => max($sizes)],
            'selling_prices' => ['min' => min($sellingPrices), 'max' => max($sellingPrices)],
            'sellers' => $sellers,
            'variants_carat' => $variantsCarat,
        ]);

        return $path;
    }

    protected function extractSize($variant)
    {
        return floatval($this->sanitizeNumber($variant['size']['title']));
    }

    protected function extractCarat($product)
    {
        foreach ($product['specifications'] as $specification) {
            foreach ($specification['attributes'] as $attribute) {
                if (strpos($attribute['title'], 'عیار') !== false) {
                    foreach ($attribute['values'] as $attributeValue) {
                        $carat = $this->sanitizeNumber($attributeValue);
                        if ($carat) {
                            $carat = floatval($carat);
                            switch ($carat) {
                                case 18:
                                case 750:
                                    return 18;
                                case 24:
                                case 995:
                                    return 24;
                                default:
                                    return $carat;
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    public function analyze()
    {
        $analyzeFilePath = $this->path('index.json');
        if (! file_exists($analyzeFilePath)) {
            $this->downloadSearchPages(1, 1);
            $firstSearchPagePath = $this->path('search', '1.json');
            if (
                file_exists($firstSearchPagePath) &&
                ($response = $this->readJson($firstSearchPagePath)) &&
                (isset($response['data']['pager']['total_pages'])) && ($response['data']['pager']['total_pages'] > 1)
            ) {
                $this->downloadSearchPages(2, $response['data']['pager']['total_pages']);
            }
            $this->downloadProductPages();
            $this->writeAnalyzeFile($analyzeFilePath);
        }

        return $this->readJson($analyzeFilePath);
    }
}
