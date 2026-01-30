<?php

date_default_timezone_set('Asia/Tehran');
set_time_limit(120);

include 'DigiGold.php';
$dg = new DigiGold(implode(DIRECTORY_SEPARATOR, [__DIR__, 'cache', date('Y-m-d-H').'-'.intval(date('i')/5)*5]));
$analyze = $dg->analyze();

$analyzeFilePath = __DIR__ . DIRECTORY_SEPARATOR . 'analyze.json';
file_put_contents($analyzeFilePath, json_encode((array)$analyze, JSON_UNESCAPED_UNICODE));
