#!/usr/local/bin/php
<?php
//autoloading and config
require_once __DIR__ . '/../vendor/autoload.php';
$config = include __DIR__ . '/../config.php';
$nexmo = new Nexmo\Client(new \Nexmo\Client\Credentials\Basic($config['nexmo']['key'], $config['nexmo']['secret']));

$service = new \Compliance\Service($config);

//manual [u]rl
$getopt = getopt('u:');

if(!isset($getopt['u'])){
    echo "-u url" . PHP_EOL;
    return;
}

$url = $getopt['u'];
error_log('manually adding url: ' . $url);

//add to db
$service->addPage($url);
