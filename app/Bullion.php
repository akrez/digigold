<?php

namespace App;

class Bullion
{
    protected $basePath;

    protected function path($dir, $fileName = null)
    {
        $array = [$this->basePath, $dir];
        if ($fileName !== null) {
            $array[] = $fileName;
        }

        return $this->implodePathSegments($array);
    }

    protected function implodePathSegments($dirs)
    {
        return implode(DIRECTORY_SEPARATOR, $dirs);
    }

    protected function mkdir($rawPath)
    {
        file_exists($rawPath) || mkdir($rawPath, 0777, true);
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

    protected function sendMultiGet(array $urls, $fn, $chunkLength = 60)
    {
        foreach (array_chunk($urls, $chunkLength) as $chunkedUrls) {
            $handlers = [];
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
                $handlers[] = $ch;
            }
            $this->sendMultiRequest($handlers, $fn);
            unset($chunkedUrls);
        }
    }

    protected function sendMultiRequest(array $handlers, $fn)
    {
        $multiCurl = curl_multi_init();
        foreach ($handlers as $handler) {
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

    protected function formatItem($title, $size, $url, $image, $seller, $source, $price)
    {
        $pricePerGram = ($price / $size);

        return [
            'title' => $title,
            'size' => $size,
            'url' => $url,
            'image' => $image,
            'seller' => $seller,
            'source' => $source,
            'price' => ['r' => $price, 'f' => number_format($price)],
            'pricePerGram' => ['r' => $pricePerGram, 'f' => number_format($pricePerGram)],
        ];
    }
}
