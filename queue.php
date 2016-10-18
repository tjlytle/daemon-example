#!/usr/bin/php
<?php
//autoloading and config
require_once 'vendor/autoload.php';
$config = include 'config.php';

$queue = new \Pheanstalk\Pheanstalk('127.0.0.1');

if($queue->getConnection()->isServiceListening()){
    echo "Server is online." . PHP_EOL;
}

$getopt = getopt('q:');

echo "Queues: " . PHP_EOL;
echo implode(PHP_EOL, $queue->listTubes()) . PHP_EOL;

if(isset($getopt['q'])){
    var_dump($queue->statsTube($getopt['q'])->getArrayCopy());
}