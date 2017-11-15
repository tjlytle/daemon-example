#!/usr/local/bin/php
<?php
//autoloading and config
require_once __DIR__ . '/../vendor/autoload.php';
$config = include __DIR__ . '/../config.php';
$service = new \Wakeup\Service($config);

//[p]hone, [n]ame [t]ime [m]essage
$getopt = getopt('p:n:t:m:', ['list']);
$getopt = array_merge(['m' => null], $getopt);
$args = array_keys($getopt);

if(!array_diff(['p', 'n', 't'], $args)){
    $service->addWakeup(new DateTime($getopt['t']), $getopt['p'], $getopt['n'], $getopt['m']);
    error_log('added request to database');

    return;
} elseif(in_array('list', $args)) {
    foreach($service->fetchAllWakeups() as $row){
        echo "[{$row['date']}] {$row['number']}\n  {$row['message']}\n";
    }

    return;
}

echo "-p phone_number" . PHP_EOL;
echo "-n name" . PHP_EOL;
echo "-t time" . PHP_EOL;
echo "-m message" . PHP_EOL;