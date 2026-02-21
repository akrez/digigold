<?php

use App\DigiKalaGold;
use App\Gold;
use App\SnappShopGold;

ini_set('memory_limit', '-1');
date_default_timezone_set('Asia/Tehran');
set_time_limit(180);

include implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'vendor', 'autoload.php']);

$cacheKey = date('Y-m-d-H').'-'.intval((date('i') / 5) + 1) * 5;

$gold = new Gold;
$analyze = $gold->analyzeAll($cacheKey, [
    'DigiKala' => DigiKalaGold::class,
    'SnappShop' => SnappShopGold::class,
]);
file_put_contents(
    implode(DIRECTORY_SEPARATOR, [__DIR__, 'index.json']),
    json_encode((array) $analyze, JSON_UNESCAPED_UNICODE)
);
