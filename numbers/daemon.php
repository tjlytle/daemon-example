#!/usr/local/bin/php
<?php
//autoloading and config
require_once '../vendor/autoload.php';
$config = include '../config.php';

//setup queue
$queue = new \Pheanstalk\Pheanstalk('127.0.0.1');
$queue->watchOnly('normalize');

//setup nexmo
$nexmo = new Nexmo\Client(new \Nexmo\Client\Credentials\Basic($config['nexmo']['key'], $config['nexmo']['secret']));

//setup signals
$run = true;
pcntl_signal(SIGINT, function() use (&$run){
    $run = false;
    error_log('shutting down');
});
declare(ticks=1);

//queue will block until there's a job
while($run AND $job = $queue->reserve()){
    //once we have a job, unserialize the data
    error_log('got job: ' . $job->getId());
    $data = json_decode($job->getData(), true);
    $row = $data['row'];
    $file = $data['file'];

    //create HTTP request
    $request = new \Zend\Diactoros\Request(
        'https://api.nexmo.com/ni/basic/json',
        'POST',
        'php://temp',
        ['Content-Type' => 'application/json']
    );

    //set request data
    $request->getBody()->write(json_encode([
        'country' => $row[0],
        'number' => $row[1]
    ]));

    //call API and parse response
    $response = $nexmo->send($request);
    $data = $response->getBody()->getContents();
    $data = json_decode($data, true);

    //open file for writing
    $fp = fopen($file, 'a');
    error_log('writing to ' . $file);

    //no number data found
    if(!$data OR !isset($data['status']) OR !($data['status'] == 0)){
        fputcsv($fp, array_merge($row, [null, null]));
    //number data found
    } else {
        fputcsv($fp, array_merge($row, [
            $data['international_format_number'],
            $data['national_format_number']
        ]));
    }

    //close file and mark job as complete
    fclose($fp);
    $queue->delete($job);
    error_log('deleted job: ' . $job->getId());
}
