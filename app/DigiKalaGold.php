<?php

namespace App;

class DigiKalaGold extends Gold
{
    public function __construct(public string $source, public array $basePathSegments)
    {
        $this->basePath = $this->implodePathSegments($basePathSegments);
        foreach (['search', 'product'] as $dir) {
            $path = $this->path($dir);
            $this->mkdir($path);
        }
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
            if (
                isset($response['data']['pager']) &&
                ($pagination = $response['data']['pager'])
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
            if (
                isset($response['data']['product']['id']) &&
                ($productId = $response['data']['product']['id'])
            ) {
                $path = $this->path('product', $productId.'.json');
                $this->writeJson($path, $response);
            }
        });
    }

    protected function writeAnalyzeFile($path)
    {
        $items = [];

        $productPaths = glob($this->path('product', '*.json'));
        foreach ($productPaths as $productPath) {
            try {
                $data = $this->readJson($productPath)['data'];
                if (empty($data['product']['variants'])) {
                    continue;
                }
                $carat = $this->extractCarat($data['product']);
                foreach ($data['product']['variants'] as $variant) {
                    $size = $this->extractSize($variant);
                    //
                    $items[strval($carat)][] = $this->formatItem(
                        $data['product']['title_fa'],
                        $size,
                        $data['seo']['open_graph']['url'],
                        $data['seo']['open_graph']['image'],
                        $variant['seller']['title'],
                        $this->source,
                        floatval($variant['price']['selling_price'] / 10)
                    );
                }
            } catch (\Throwable $th) {
            } catch (\Exception $e) {
            }
        }

        $this->writeJson($path, $items);

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
            }
        }

        return static::CARAT_10000;
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
