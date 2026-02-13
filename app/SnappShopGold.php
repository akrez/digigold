<?php

namespace App;

class SnappShopGold extends Gold
{
    public function __construct(public string $source, public array $basePathSegments)
    {
        $this->basePath = $this->implodePathSegments($basePathSegments);
        foreach (['search', 'product'] as $dir) {
            $path = $this->path($dir);
            $this->mkdir($path);
        }
    }
    
    protected function extractSize($attributes, $attributeIds)
    {
        foreach ($attributeIds as $attributeId) {
            if (isset($attributes[$attributeId['attribute_value_id']])) {
                $vazn = $attributes[$attributeId['attribute_value_id']];
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
                            return static::CARAT_18;
                        case 24:
                        case 995:
                            return static::CARAT_24;
                        case 999:
                        case 999.9:
                            return static::CARAT_9999;
                        default:
                    }
                }
            }
        }

        return static::CARAT_10000;
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
        $this->sendMultiRequest($handlers, function ($response) {
            $response = json_decode($response, true);
            if (
                isset($response['data']['structure'][2]['pagination']) &&
                ($pagination = $response['data']['structure'][2]['pagination'])
            ) {
                $path = $this->path('search', $pagination['current_page'].'.json');
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
            if (
                ! empty($response['data']['page']['json_ld'][0]['sku']) &&
                ($sku = $response['data']['page']['json_ld'][0]['sku'])
            ) {
                $path = $this->path('product', $sku.'.json');
                $this->writeJson($path, $response);
            }
        });
    }

    protected function writeAnalyzeFile($path)
    {
        $items = [];
        $sellers = [];
        $attributes = [];

        $productPaths = glob($this->path('product', '*.json'));
        foreach ($productPaths as $productPath) {

            try {
                $data = $this->readJson($productPath)['data'];
                $productId = $data['page']['json_ld'][0]['sku'];
                $productTitleFa = $data['content']['title_fa'];
                $carat = $this->extractCarat($data['attributes']);
                foreach ($data['vendors'] as $vendor) {
                    if (empty($sellers[$vendor['id']])) {
                        $sellers[$vendor['id']] = $vendor['title'];
                    }
                }
                foreach ($data['configurable_attribute'] as $configurableAttribute) {
                    $attributes[$configurableAttribute['value']['id']] = $configurableAttribute['value']['title'];
                }
                foreach ($data['variants'] as $variant) {
                    $size = $this->extractSize($attributes, $variant['attribute_ids']);
                    foreach ($variant['vendor'] as $vendor) {
                        $price = floatval($vendor['special_price'] ? $vendor['special_price'] : $vendor['price']);
                        $items[strval($carat)][] = $this->formatItem(
                            $productTitleFa,
                            $size,
                            'https://snappshop.ir/product/snp-'.$productId,
                            (isset($data['images']['0']['src']) ? $data['images']['0']['src'] : null),
                            $sellers[$vendor['vendor_id']],
                            $this->source,
                            $price
                        );
                    }
                }
            } catch (\Throwable $th) {
            } catch (\Exception $e) {
            }
        }

        $this->writeJson($path, $items);

        return $path;
    }

    public function analyze()
    {
        $analyzeFilePath = $this->path('analyze.json');
        if (! file_exists($analyzeFilePath)) {
            $this->downloadSearchPages(1, 1);
            $firstSearchPagePath = $this->path('search', '1.json');
            if (
                file_exists($firstSearchPagePath) &&
                ($response = $this->readJson($firstSearchPagePath)) &&
                isset($response['data']['structure'][2]['pagination']) &&
                ($pagination = $response['data']['structure'][2]['pagination']) &&
                ($pagination['total_pages'] > 1)
            ) {
                $this->downloadSearchPages(2, $pagination['total_pages']);
            }
            $this->downloadProductPages();
            $this->writeAnalyzeFile($analyzeFilePath);
        }

        return $this->readJson($analyzeFilePath);
    }
}
