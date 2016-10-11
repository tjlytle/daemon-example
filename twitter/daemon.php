<?php
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use GuzzleHttp\Psr7;

//autoloading and config
require_once '../vendor/autoload.php';

error_log('pid: ' . getmypid());

$getStream = function(){
    $config = include '../config.php';
    error_log('loaded config');

    //http client
    $client = new HttpClient();

    //oauth setup
    $oauth = new Oauth1($config['oauth']);
    $client->getConfig('handler')->push($oauth);

    error_log('setup oauth');

    //tracked keywords
    $track = $config['track'];
    error_log('tracking: ' . implode(',', $track));

    //request for twitter's stream api
    $request = new Psr7\Request(
        'POST',
        'https://stream.twitter.com/1.1/statuses/filter.json',
        ['Content-Type' => 'application/x-www-form-urlencoded'],
        //set the track keywords
        http_build_query([
	    'track' => implode(',', $track)
        ])
    );

    error_log('created stream request');

    //get the streamed response
    $response = $client->send($request, ['stream' => true, 'auth' => 'oauth']);
    $stream = $response->getBody();

    return $stream;
};

//read lines from the response
$count = 0;
$start = $time = time();
$run = true;

//stats call
$stats = function($count, $start){
    error_log('tweets collected: ' . $count);
    $elapsed = time() - $start;
    error_log('tweets / minute: ' . $count/($elapsed/60));
    return time();
};

//shutdown
$shutdown = function($signal) use (&$run){
    error_log('caught signal: ' . $signal);
    $run = false;
};

//reload
$reload = function($signal) use ($getStream, &$stream){
    error_log('caught signal: ' . $signal);
    $stream = $getStream();
};

//register the handler
pcntl_signal(SIGINT, $shutdown);
pcntl_signal(SIGHUP, $reload);

$stream = $getStream();
error_log('connected to stream');
while(!$stream->eof() AND $run){
    $tweet = Psr7\readline($stream);
    $tweet = json_decode($tweet, true);
    if(isset($tweet['text'])){
        $count++;
        file_put_contents('tweets.txt', $tweet['text'] . PHP_EOL, FILE_APPEND);
    }

    if(time() > ($time + 30)){
        $time = $stats($count, $start);
    }

    //only process the signals here
    pcntl_signal_dispatch();
}

//do some shutdown like things
error_log('shutting down');
error_log('closing stream connection');
$stream->close();
error_log('marking archive');
file_put_contents('tweets.txt', '--paused--' . PHP_EOL, FILE_APPEND);
error_log('final stats');
$stats($count, $start);