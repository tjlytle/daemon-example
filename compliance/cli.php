#!/usr/local/bin/php
<?php
//autoloading and config
require_once __DIR__ . '/../vendor/autoload.php';
$config = include __DIR__ . '/../config.php';
$nexmo = new Nexmo\Client(new \Nexmo\Client\Credentials\Basic($config['nexmo']['key'], $config['nexmo']['secret']));
$service = new \Compliance\Service($config);
$queue = new \Pheanstalk\Pheanstalk($config['beanstalk']['host']);
$queue->useTube('crawl');

//base [u]rl
$getopt = getopt('d:', ['stats']);

if(isset($getopt['stats'])){

}

if(!isset($getopt['d'])){
    echo "-d domain" . PHP_EOL;
    return;
}

$domain = $getopt['d'];
$url = 'https://' . $domain;

error_log('seeding search with: ' . $url);

//add to db
$service->addPage($url);

//seed crawl
$queue->put('crawl', json_encode([
    'url' => $url,
]));

error_log('job started');
