<?php

class SnappGold
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

    protected function sendMultiPost(array $handlers, $fn, $chunkLength = 60)
    {
        foreach (array_chunk($handlers, $chunkLength) as $chunkedHandlers) {

            $multiCurl = curl_multi_init();

            foreach ($chunkedHandlers as $handler) {
                curl_multi_add_handle($multiCurl, $handler);
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

    protected function extractSize($configurableAttributes, $attributeIds)
    {
        foreach ($attributeIds as $attributeId) {
            if (isset($configurableAttributes[$attributeId['attribute_value_id']])) {
                $vazn = $configurableAttributes[$attributeId['attribute_value_id']];
                if (strpos($vazn, 'گرم') !== false) {
                    return floatval($this->sanitizeNumber($vazn));
                }
            }
        }

        return null;
    }

    protected function extractCarat($attributes)
    {
        foreach ($attributes as $attribute) {
            if (strpos($attribute['title'], 'عیار') !== false) {
                $carat = $this->sanitizeNumber($attribute['value']);
                if ($carat) {
                    $carat = floatval($carat);
                    switch ($carat) {
                        case 18:
                        case 750:
                            return 18;
                        case 24:
                        case 995:
                        case 999:
                            return 24;
                        default:
                            return $carat;
                    }
                }
            }
        }

        return null;
    }

    protected function downloadSearchPages($fromPage, $toPage)
    {
        $handlers = [];
        for ($i = $fromPage; $i <= $toPage; $i++) {
            $path = $this->path('search', $i.'.json');
            if (! file_exists($path)) {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => 'https://apix.snappshop.ir/landing/v2?lat=35.00&lng=51.00',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_POSTFIELDS => json_encode([
                        'exclude_filters' => true,
                        'is_available' => true,
                        'page_type' => 'category',
                        'render' => 4,
                        'skip' => $i,
                        'slug' => 'gold-bullion',
                    ]),
                    CURLOPT_HTTPHEADER => [
                        'accept: */*',
                        'accept-language: en-US,en;q=0.9,zh-CN;q=0.8,zh;q=0.7,ar;q=0.6,fa;q=0.5',
                        'content-type: application/json',
                        'origin: https://snappshop.ir',
                        'priority: u=1, i',
                        'referer: https://snappshop.ir/',
                        's-device: DESKTOP',
                        's-device-source: shop',
                        'sec-ch-ua-platform: "Linux"',
                        'sec-fetch-dest: empty',
                        'sec-fetch-mode: cors',
                        'sec-fetch-site: same-site',
                        'user-agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',
                        'x-origin: https://snappshop.ir',
                    ],
                ]);
                $handlers[] = $ch;
            }
        }
        if (empty($handlers)) {
            return;
        }
        $this->sendMultiPost($handlers, function ($response) {
            $response = json_decode($response, true);
            if (! empty($response['data']['structure'][2]['pagination']['current_page'])) {
                $path = $this->path('search', $response['data']['structure'][2]['pagination']['current_page'].'.json');
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
            if (empty($pageContent['data']['structure'][2]['items'])) {
                continue;
            }
            foreach ($pageContent['data']['structure'][2]['items'] as $product) {
                $productId = str_replace('snp-', '', basename($product['href']));
                $path = $this->path('product', $productId.'.json');
                if (! file_exists($path)) {
                    $urls[] = 'https://apix.snappshop.ir/products/v2/'.$productId.'?lat=35.00&lng=51.00';
                }
            }
        }

        $this->sendMultiGet($urls, function ($response) {
            $response = json_decode($response, true);
            if (! empty($response['data']['page']['json_ld'][0]['sku'])) {
                $path = $this->path('product', $response['data']['page']['json_ld'][0]['sku'].'.json');
                $this->writeJson($path, $response);
            }
        });
    }

    protected function writeAnalyzeFile($path)
    {
        $vendorsCarat = [];
        $sellers = [];
        $sellingPrices = [];
        $sizes = [];
        $configurableAttributes = [];

        $productPaths = glob($this->path('product', '*.json'));
        foreach ($productPaths as $productPath) {

            try {
                $data = $this->readJson($productPath)['data'];
                $productId = $data['page']['json_ld'][0]['sku'];
                $productTitleFa = $data['content']['title_fa'];
                $carat = $this->extractCarat($data['attributes']);
                foreach ($data['vendors'] as $vendor) {
                    if (! isset($sellers[$vendor['id']])) {
                        $sellers[$vendor['id']] = [
                            'id' => $vendor['id'],
                            'title' => $vendor['title'],
                            'count' => 0,
                        ];
                    }
                }
                foreach ($data['configurable_attribute'] as $configurableAttribute) {
                    $configurableAttributes[$configurableAttribute['value']['id']] = $configurableAttribute['value']['title'];
                }
                foreach ($data['variants'] as $variant) {
                    $size = $this->extractSize(
                        $configurableAttributes,
                        $variant['attribute_ids'],
                    );
                    foreach ($variant['vendor'] as $vendor) {
                        $sellingPrice = floatval($vendor['special_price'] ? $vendor['special_price'] : $vendor['price']);
                        $sellerId = $vendor['vendor_id'];
                        //
                        $sellerTitle = $sellers[$sellerId]['title'];
                        $pricePerGram = ($sellingPrice / $size);
                        //
                        $vendorsCarat[strval($carat)][] = [
                            'id' => $productId,
                            'title_fa' => $productTitleFa,
                            'selling_price' => $sellingPrice,
                            'seller_id' => $sellerId,
                            'seller_title' => $sellerTitle,
                            'size' => $size,
                            'url' => 'https://snappshop.ir/product/snp-'.$productId,
                            'image' => (isset($data['images']['0']['src']) ? $data['images']['0']['src'] : null),
                            '_carat' => $carat,
                            '_selling_price_formatted' => number_format($sellingPrice),
                            '_price_per_gram' => $pricePerGram,
                            '_price_per_gram_formatted' => number_format($pricePerGram),
                            'source' => 'snappshop',
                        ];
                        //
                        $sizes[strval($size)] = $size;
                        $sellingPrices[strval($sellingPrice)] = $sellingPrice;
                    }
                }
            } catch (\Throwable $th) {
            } catch (\Exception $e) {
            }
        }

        usort($sellers, function ($a, $b) {
            return $b['count'] - $a['count'];
        });
        //
        ksort($vendorsCarat);
        foreach ($vendorsCarat as $carat => $variants) {
            usort($variants, function ($a, $b) {
                return $a['_price_per_gram'] - $b['_price_per_gram'];
            });
            $vendorsCarat[$carat] = $variants;
        }

        $this->writeJson($path, [
            'date' => date('Y-m-d H:i:s'),
            'sizes' => ['min' => min($sizes), 'max' => max($sizes)],
            'selling_prices' => ['min' => min($sellingPrices), 'max' => max($sellingPrices)],
            'sellers' => $sellers,
            'variants_carat' => $vendorsCarat,
        ]);

        return $path;
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
                (isset($response['data']['structure'][2]['pagination']['total_pages'])) && ($response['data']['structure'][2]['pagination']['total_pages'] > 1)
            ) {
                $this->downloadSearchPages(2, $response['data']['structure'][2]['pagination']['total_pages']);
            }
            $this->downloadProductPages();
            $this->writeAnalyzeFile($analyzeFilePath);
        }

        return $this->readJson($analyzeFilePath);
    }
}
