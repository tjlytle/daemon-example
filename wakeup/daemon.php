#!/usr/bin/php
<?php
//autoloading and config
require_once '../vendor/autoload.php';
$config = include '../config.php';
$service = new \Wakeup\Service($config);

//setup queue
$queue = new \Pheanstalk\Pheanstalk('127.0.0.1');
$queue->watchOnly('wakeup');

//setup nexmo
$nexmo = new Nexmo\Client(new \Nexmo\Client\Credentials\Basic($config['nexmo']['key'], $config['nexmo']['secret']));

//setup signals
$run = true;
pcntl_signal(SIGINT, function() use (&$run){
    $run = false;
    error_log('shutting down');
});
declare(ticks=1);

//create HTTP request
$request = new \Zend\Diactoros\Request(
    'https://api.nexmo.com/tts/json',
    'POST',
    'php://temp',
    ['Content-Type' => 'application/json']
);

//set request data
$request->getBody()->write(json_encode([
    'from' => $config['nexmo']['from'],
    'to'   => '',
    'text' => ''
]));

//call API and parse response
$response = $nexmo->send($request);
$data = $response->getBody()->getContents();
$data = json_decode($data, true);
