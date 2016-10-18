#!/usr/bin/php
<?php
//autoloading and config
require_once '../vendor/autoload.php';
$config = include '../config.php';
$service = new \Wakeup\Service($config);

//[p]hone, [n]ame [t]ime [m]essage
$getopt = getopt('p:n:t:m:', ['list']);
$getopt = array_merge(['m' => null], $getopt);
$args = array_keys($getopt);

if(!array_diff(['p', 'n', 't'], $args)){
    $service->addWakeup(new DateTime($getopt['t']), $getopt['p'], $getopt['n'], $getopt['m']);
    error_log('added request to database');
} elseif(in_array('list', $args)) {
    foreach($service->fetchAllWakeups() as $row){
        echo "[{$row['date']}] {$row['number']}\n  {$row['message']}\n";
    }
}