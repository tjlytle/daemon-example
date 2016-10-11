<?php
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use GuzzleHttp\Psr7;

//autoloading and config
require_once '../vendor/autoload.php';

$daemon = new TwitterDaemon('../config.php', 'tweets.txt');

$daemon->start();


class TwitterDaemon
{
    /**
     * @var string
     */
    protected $config;

    /**
     * @var string
     */
    protected $output;

    /**
     * @var bool
     */
    protected $run = false;

    /**
     * @var Stream
     */
    protected $stream;

    protected $count;

    protected $start;

    public function __construct($config, $output)
    {
        if(!is_string($config) OR !file_exists($config)){
            throw new Exception('config must be file that exists');
        }

        $this->config = $config;

        if(!is_string($output) OR !touch($output)){
            throw new Exception('output must be writable file');
        }

        $this->output = $output;

        pcntl_signal(SIGINT, [$this, 'signalStop']);
        pcntl_signal(SIGHUP, [$this, 'signalReload']);
        pcntl_signal(SIGTERM, [$this, 'signalStop']);
    }

    protected function setup()
    {
        $config = include($this->config);
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

        $this->stream = $stream;
    }

    protected function run()
    {
        //read lines from the response
        $this->count = 0;
        $this->start = $time = time();

        while(!$this->stream->eof() AND $this->run){
            $tweet = Psr7\readline($this->stream);
            $tweet = json_decode($tweet, true);
            if(isset($tweet['text'])){
                $this->count++;
                file_put_contents($this->output, $tweet['text'] . PHP_EOL, FILE_APPEND);
            }

            if(time() > ($time + 30)){
                $time = time();
                $this->outputStats();
            }

            //only process the signals here
            pcntl_signal_dispatch();
        }

    }

    protected function outputStats()
    {
        error_log('tweets collected: ' . $this->count);
        $elapsed = time() - $this->start;
        error_log('tweets / minute: ' . $this->count/($elapsed/60));
    }

    protected function shutdown()
    {
        error_log('shutting down');
        error_log('closing stream connection');
        $this->stream->close();
        error_log('marking archive');
        file_put_contents('tweets.txt', '--paused--' . PHP_EOL, FILE_APPEND);
        error_log('final stats');
        $this->outputStats();
    }

    public function signalStop($signal)
    {
        error_log('caught shutdown signal [' . $signal . ']');
        $this->run = false;
    }

    public function signalReload($signal)
    {
        error_log('caught reload signal [' . $signal . ']');
        $this->setup();
    }

    public function start()
    {
        $this->run = true;
        $this->setup();
        $this->run();
        $this->shutdown();
    }
}
