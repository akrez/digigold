<?php

date_default_timezone_set('Asia/Tehran');
set_time_limit(120);

$currentPath = __DIR__ . DIRECTORY_SEPARATOR;
$basePath = $currentPath . '..' . DIRECTORY_SEPARATOR;

include  $basePath . 'DigiGold.php';
$dg = new DigiGold($basePath . implode(DIRECTORY_SEPARATOR, ['cache', date('Y-m-d-H').'-'.intval(date('i')/5)*5]));
$analyze = $dg->analyze();

$analyzeFilePath = $currentPath . 'index.json';
file_put_contents($analyzeFilePath, json_encode((array)$analyze, JSON_UNESCAPED_UNICODE));
