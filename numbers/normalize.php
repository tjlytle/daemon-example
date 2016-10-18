#!/usr/local/bin/php
<?php
//autoloading and config
require_once __DIR__ . '/../vendor/autoload.php';
$config = include __DIR__ . '/../config.php';

//require an output filename
$getopt = getopt('f:');
if(!isset($getopt['f'])){
    echo "use -f to set output file" . PHP_EOL;
    return;
}

//setup queue
$queue = new \Pheanstalk\Pheanstalk('127.0.0.1');
$queue->useTube('normalize');

//create jobs for each number
$count = 0;
while($row = fgetcsv(STDIN)){
    $queue->put(json_encode([
        'row' => $row,
        'file' => $getopt['f']
    ]));
    $count++;
}

echo "Added $count to queue." . PHP_EOL;
