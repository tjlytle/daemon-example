#!/usr/local/bin/php
<?php
//autoloading and config
require_once __DIR__ . '/../vendor/autoload.php';
$config = include __DIR__ . '/../config.php';
$service = new \Wakeup\Service($config);

//setup queue
$queue = new \Pheanstalk\Pheanstalk($config['beanstalk']['host']);
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

$run = true;
while($run){

    //wait for job (timeout in 10 seconds)
    $job = $queue->reserve(10);
    if(!$job){
        error_log('no job found');
        continue;
    }
    $call = json_decode($job->getData(), true);

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
        'to'   => $call['number'],
        'text' => $call['message']
    ]));

    //call API and parse response
    error_log('making call to ' . $call['number'] . ' with ' . $call['message']);
    $response = $nexmo->send($request);
    $data = $response->getBody()->getContents();
    $data = json_decode($data, true);

    if(0 != $data['status']){
        error_log('api error: ' . $data['status']);
        //try again in a minute
        $queue->release($job, \Pheanstalk\PheanstalkInterface::DEFAULT_PRIORITY, 60);
        continue;
    }

    //log that the call was made
    error_log('made call: ' . $data['call_id']);
    $queue->delete($job);
}