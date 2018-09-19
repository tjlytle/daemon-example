#!/usr/local/bin/php
<?php
//autoloading and config
require_once __DIR__ . '/../vendor/autoload.php';
$config = include __DIR__ . '/../config.php';
$service = new \Compliance\Service($config);

//setup queue
$queue = new \Pheanstalk\Pheanstalk($config['beanstalk']['host']);
$queue->watchOnly('crawl');

//http client
$client = new GuzzleHttp\Client([
    'headers' => [
        'User-Agent' => 'tjlytle/daemon-example'
    ]
]);

//setup signals
$run = true;
pcntl_signal(SIGINT, function() use (&$run){
    $run = false;
    error_log('shutting down');
});
declare(ticks=1);

error_log('listening for job');
while($run){
    //queue will block until there's a job
    $job = $queue->reserve(10);
    if(!$job){
        error_log('queue timeout');
        continue;
    }

    $crawl = $job->getData();

    //try to fetch page
    try{
        $response = $client->get($crawl);
        $page = $response->getBody()->getContents();
    } catch(Exception $e) {
        error_log('could not crawl: ' . $crawl);
        $queue->bury($job);
    }

    error_log('fetched: ' . $crawl);

    //parse the page
    try{
        //parse as XML so we can search the structure
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML($page);
        $xml = simplexml_import_dom($doc);
        if(!$xml){
            throw new Exception('could not convert node');
        }
    } catch (Exception $exception) {
        error_log('could not parse: ' . $crawl);
        $queue->bury($job);
        continue;
    }

    //check for compliance issues
    $found = [];
    foreach($config['compliance']['keywords'] as $keyword){
        if($count = substr_count(strtolower($page), strtolower($keyword))){
            $found[] = $keyword;
            error_log('found keyword `' . $keyword . '` on page: ' . $crawl);
        }
    }

    $service->updatePage($crawl, $found);

    $domain = parse_url($crawl, PHP_URL_HOST);
    $protocol = parse_url($crawl, PHP_URL_SCHEME);

    //find links to crawl
    foreach($xml->xpath('//a') as $link){
        $url = (string) $link['href'];
        $url = strtok($url, "#");
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (!empty($scheme) && !in_array(strtolower($scheme), ['http', 'https'])) {
            error_log('skipping (not http): ' . $url);
            continue;
        }

        if(!$host){
            $host = $domain;
            if ($url[0] === '/') {
                $url = $host . $url;
            } else {
                $url = $crawl . $url;
            }

            error_log('added host: ' . $url);
        }

        if(!parse_url($url, PHP_URL_SCHEME)) {
            $url = $protocol . '://' . $url;
            error_log('added protocol: ' . $url);
        }

        //restrict to same host
        if(false === strpos($host, $domain)){
            error_log('skipping (not same domain): ' . $url);
            continue;
        }

        $service->addPage($url);
        error_log('added: ' . $url);
    }

    $queue->delete($job);
}
