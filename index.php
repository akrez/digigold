<?php

class DigiGold
{

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

    function writeJson($path, ?array $arrayContent)
    {
        file_put_contents($path, json_encode((array)$arrayContent, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    function sendGetJson($url)
    {
        $ch = $this->makeCurlHandler($url);
        $response = curl_exec($ch);

        return [
            'successful' => (curl_errno($ch) == 0),
            'error' => curl_error($ch),
            'response' => $response,
            'response_json' => json_decode($response, true),
        ];
    }

    function sendSearch($page)
    {
        $this->mkdir('search');
        $path = $this->path('search', $page . '.json');

        $exists = file_exists($path);
        if ($exists) {
            return json_decode(file_get_contents($path), true);
        }
        $res = $this->sendGetJson('https://api.digikala.com/v1/categories/bullion/search/?page=' . $page . '&price%5Bmax%5D=510000000&price%5Bmin%5D=10000000&sort=7');
        $this->writeJson($path, $res['response_json']);
        return $res['response_json'];
    }

    function search()
    {
        $page = 1;
        while (true) {
            $res = $this->sendSearch($page);
            if (empty($res['data']['products'])) {
                return $page - 1;
            } else {
                $page++;
            }
        }
    }

    function sendProducts($page, $productIds)
    {
        $this->mkdir('product');
        $multiCurl = curl_multi_init();

        $count = 0;
        foreach ($productIds as $productId) {
            $path = $this->path('product', $productId . '.json');
            $exists = file_exists($path);
            if (!$exists) {
                $count++;
                curl_multi_add_handle($multiCurl, $this->makeCurlHandler('https://api.digikala.com/v2/product/' . $productId . '/'));
            }
        }

        if (!$count) {
            return 0;
        }

        do {
            curl_multi_exec($multiCurl, $running);
        } while ($running > 0);

        while (($info = curl_multi_info_read($multiCurl)) !== false) {
            $ch = $info['handle'];
            $response = curl_multi_getcontent($ch);
            $response = json_decode($response, true);
            $path = $this->path('product', $response['data']['product']['id'] . '.json');
            $this->writeJson($path, $response);
            curl_multi_remove_handle($multiCurl, $ch);
        }
    }

    function product()
    {
        $path = $this->path('search', '*.json');
        $files = glob($path);
        natcasesort($files);
        foreach ($files as $file) {
            $page = basename($file, '.json');
            $pageContent = json_decode(file_get_contents($file), true);
            $productIds = [];
            if (!empty($pageContent['data']['products'])) {
                foreach ($pageContent['data']['products'] as $product) {
                    $productIds[] = $product['id'];
                }
                $this->sendProducts($page, $productIds);
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

$dg = new DigiGold();
$dg->search();
$dg->product();
var_dump($dg->analyze());
