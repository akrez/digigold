<?php

class DigiGold
{
    public function __construct(
        protected int $minPrice,
        protected int $maxPrice,
        protected int $sort,
    ) {}


    function path($dir, $fileName = null)
    {
        $array = ['.', 'cache', date('Y-m-d-H'), $dir];
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

    function makeCurlHandler($url)
    {
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

        return $ch;
    }

    function readJson($path): array
    {
        return (array)json_decode(file_get_contents($path), true);
    }

    function writeJson($path, ?array $arrayContent)
    {
        file_put_contents($path, json_encode((array)$arrayContent, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    function search()
    {
        $this->mkdir('search');
        $page = 1;
        while (true) {
            $path = $this->path('search', $page . '.json');
            if (! file_exists($path)) {
                $url = 'https://api.digikala.com/v1/categories/bullion/search/?' . http_build_query(['page' => $page, 'price[min]' => $this->minPrice,  'price[max]' => $this->maxPrice, 'sort' => 7]);
                $ch = $this->makeCurlHandler($url);
                $response = (array) json_decode(curl_exec($ch), true);
                $this->writeJson($path, $response);
            }
            $response = $this->readJson($path);
            if (empty($response['data']['products'])) {
                return $page;
            }
            $page++;
        }
    }

    function product()
    {
        $this->mkdir('product');

        $pageToProductIds = [];
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
                if (!file_exists($path)) {
                    $pageToProductIds[$page][] = $product['id'];
                }
            }
        }

        foreach ($pageToProductIds as $page => $productIds) {
            $multiCurl = curl_multi_init();

            foreach ($productIds as $productId) {
                curl_multi_add_handle($multiCurl, $this->makeCurlHandler('https://api.digikala.com/v2/product/' . $productId . '/'));
            }

            do {
                curl_multi_exec($multiCurl, $running);
            } while ($running > 0);

            while (($info = curl_multi_info_read($multiCurl)) !== false) {
                $ch = $info['handle'];
                $response = curl_multi_getcontent($ch);
                $response = json_decode($response, true);
                if (! empty($response['data']['product']['id'])) {
                    $path = $this->path('product', $response['data']['product']['id'] . '.json');
                    $this->writeJson($path, $response);
                }
                curl_multi_remove_handle($multiCurl, $ch);
            }
        }
    }

    function analyze()
    {
        $results = [];

        $path = $this->path('product', '*.json');
        $files = glob($path);
        foreach ($files as $file) {
            $product = json_decode(file_get_contents($file), true);
            $product = $product['data']['product'];
            $productTitleFa = $product['title_fa'];
            $ayar = $this->extractAyar($productTitleFa);
            if (empty($product['variants'])) {
                continue;
            }
            foreach ($product['variants'] as $variant) {
                // var_dump($variant);die;
                $results[$ayar][] = [
                    'id' => $product['id'],
                    'title_fa' => $product['title_fa'],
                    'selling_price' => $variant['price']['selling_price'],
                    'size' => $variant['price']['gold_price_details']['size'],
                    '_price_per_gram' => (floatval($variant['price']['selling_price']) / floatval($variant['price']['gold_price_details']['size'])),
                ];
            }
        }

        foreach ($results as $resultKey => $result) {
            usort($result, function ($a, $b) {
                return $a['_price_per_gram'] - $b['_price_per_gram'];
            });
            $results[$resultKey] = $result;
        }

        return $results;
    }

    function extractAyar($productName)
    {
        preg_match_all("/\d+/", $productName, $matches);
        $matches = reset($matches);

        if (in_array(24, $matches)) {
            if (in_array(18, $matches)) {
                return 2418;
            }
            return 24;
        } elseif (in_array(18, $matches)) {
            return 18;
        }
        return 0;
    }
}

$dg = new DigiGold(1_000_000_0, 2_000_000_0, 7);
$dg->search();
$dg->product();
var_dump($dg->analyze());
