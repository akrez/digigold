<?php

namespace App;

class Gold extends Bullion
{
    const CARAT_18 = 18;

    const CARAT_24 = 24;

    const CARAT_9999 = 999.9;

    const CARAT_10000 = 10000;

    public function analyzeAll($cacheKey, $services)
    {
        $analyzeFilePath = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'cache', $cacheKey, 'analyze.json']);
        if (! file_exists($analyzeFilePath)) {

            $items = [];
            foreach ($services as $serviceName => $serviceClass) {
                $service = new $serviceClass($serviceName, [__DIR__, '..', 'cache', $cacheKey, $serviceName]);
                foreach ($service->analyze() as $caratName => $caratItems) {
                    if (! isset($items[$caratName])) {
                        $items[$caratName] = [];
                    }
                    $items[$caratName] = array_merge($items[$caratName], $caratItems);
                }
            }

            ksort($items);
            foreach ($items as $carat => $variants) {
                usort($variants, function ($a, $b) {
                    return $a['pricePerGram']['r'] - $b['pricePerGram']['r'];
                });
                $items[$carat] = $variants;
            }

            $this->writeJson($analyzeFilePath, [
                'date' => date('Y-m-d H:i:s'),
                'items' => $items,
            ]);
        }

        return $this->readJson($analyzeFilePath);
    }
}
