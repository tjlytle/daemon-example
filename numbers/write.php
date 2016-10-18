#!/usr/bin/php
<?php
//autoloading and config
require_once '../vendor/autoload.php';
$config = include '../config.php';

//setup queue
$queue = new \Pheanstalk\Pheanstalk('127.0.0.1');
$queue->watchOnly('write');

//setup signals
$run = true;
pcntl_signal(SIGINT, function() use (&$run){
    $run = false;
    error_log('shutting down');
});
declare(ticks=1);

//track open files
$files = [];

error_log('listening for jobs');
while($run){
    //queue will block until there's a job
    $job = $queue->reserve(10);

    if(!$job){
        error_log('queue timeout');
        continue;
    }

    //once we have a job, unserialize the data
    error_log('got job: ' . $job->getId());
    $data = json_decode($job->getData(), true);
    $row = $data['write'];
    $file = $data['file'];

    if(!isset($files[$file])){
        $files[$file] = fopen($file, 'a');
    }

    fputcsv($files[$file], $row);

    //mark job as complete
    $queue->delete($job);
    error_log('deleted job: ' . $job->getId());
}