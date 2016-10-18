#!/usr/bin/php
<?php
//autoloading and config
require_once '../vendor/autoload.php';
$config = include '../config.php';
$service = new \Compliance\Service($config);

//setup queue
$queue = new \Pheanstalk\Pheanstalk($config['beanstalk']['host']);
$queue->useTube('crawl');

//setup signals
$run = true;
pcntl_signal(SIGINT, function() use (&$run){
    $run = false;
    error_log('shutting down');
});
declare(ticks=1);

while($run){
    $found = false;
    foreach ($service->fetchStalePages(new DateTime('-1 hour')) as $page){
        $queue->put($page['url']);
        $service->markRequested($page['url']);
        error_log('added ' . $page['url']);
        $found = true;
    }

    if(!$found){
        error_log('sleeping');
        sleep(10);
    }
}
