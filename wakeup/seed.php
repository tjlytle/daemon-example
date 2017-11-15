#!/usr/local/bin/php
<?php
//autoloading and config
require_once __DIR__ . '/../vendor/autoload.php';
$config = include __DIR__ . '/../config.php';
$service = new \Wakeup\Service($config);

//setup queue
$queue = new \Pheanstalk\Pheanstalk($config['beanstalk']['host']);
$queue->useTube('wakeup');

//setup signals
$run = true;
pcntl_signal(SIGINT, function() use (&$run){
    $run = false;
    error_log('shutting down');
});
declare(ticks=1);

while($run){
    //get all calls due in 1 minute (and any in the past)
    foreach($service->fetchActiveWakeup(new DateTime('1 minute')) as $call){
        //if call is in the future, delay it in the queue
        $delay = strtotime($call['date']) - time();
        if($delay < 0){
            $delay = 0;
        }

        //add the call to the queue, and mark as queued in the database
        $queue->put(json_encode($call), \Pheanstalk\PheanstalkInterface::DEFAULT_PRIORITY, $delay);
        $service->markQueued($call['request_id']);
        error_log("added {$call['request_id']} with {$delay} second delay");
    }

    //don't hammer the database
    error_log('sleeping');
    sleep(60);
}