<?php
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use GuzzleHttp\Psr7;

//autoloading and config
require_once '../vendor/autoload.php';
$config = include '../config.php';

error_log('loaded config');

//http client
$client = new HttpClient();

//oauth setup
$oauth = new Oauth1($config['oauth']);
$client->getConfig('handler')->push($oauth);

error_log('setup oauth');

//tracked keywords
$track = [
    'fail',
    'php',
    'tjlytle'
];

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

error_log('connected to stream');

//read lines from the response
$count = 0;
$start = $time = time();
while(!$stream->eof()){
    $tweet = Psr7\readline($stream);
    $tweet = json_decode($tweet, true);
    if(isset($tweet['text'])){
        $count++;
        file_put_contents('tweets.txt', $tweet['text'] . PHP_EOL, FILE_APPEND);
    }

    if(time() > ($time + 30)){
        error_log('tweets collected: ' . $count);
        $elapsed = time() - $start;
        error_log('tweets / minute: ' . $count/($elapsed/60));
        $time = time();
    }
}
