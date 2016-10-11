<?php
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use GuzzleHttp\Psr7;

//autoloading and config
require_once '../vendor/autoload.php';
$config = include '../config.php';

//http client
$client = new HttpClient();

//oauth setup
$oauth = new Oauth1($config['oauth']);
$client->getConfig('handler')->push($oauth);

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

//get the streamed response
$response = $client->send($request, ['stream' => true, 'auth' => 'oauth']);
$stream = $response->getBody();

//read lines from the response
while(!$stream->eof()){
    $tweet = Psr7\readline($stream);
    $tweet = json_decode($tweet, true);
    if(isset($tweet['text'])){
        error_log($tweet['text']);
        file_put_contents('tweets.txt', $tweet['text'] . PHP_EOL, FILE_APPEND);
    }
}
