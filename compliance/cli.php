#!/usr/bin/php
<?php
//autoloading and config
require_once '../vendor/autoload.php';
$config = include '../config.php';
$nexmo = new Nexmo\Client(new \Nexmo\Client\Credentials\Basic($config['nexmo']['key'], $config['nexmo']['secret']));

//base [u]rl, [k]eyword
$getopt = getopt('u:k:a:');
$getopt = array_merge(['m' => null], $getopt);
$args = array_keys($getopt);

if(array_diff(['u', 'k'], $args)){
    echo "-u base url" . PHP_EOL;
    echo "-k keyword" . PHP_EOL;
    echo "-a phone number to alert" . PHP_EOL;
    return;
}

//basic params
$domain  = $getopt['u'];
$keyword = $getopt['k'];

//tracking data
$crawled = [];
$follow  = ['https://' . $domain];
$found   = [];

//http client
$client = new GuzzleHttp\Client([
    'headers' => [
        'User-Agent' => 'tjlytle/daemon-example'
    ]
]);

error_log('seeding search with: ' . $domain);
error_log('looking for: ' . $keyword);

do{
    //get the next page to crawl
    $crawl = array_pop($follow);

    try{
        $response = $client->get($crawl);
        $page = $response->getBody()->getContents();
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        error_log('could not crawl page:' . $crawl);
        error_log($e->getMessage());
        continue;
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
        $crawled[] = $crawl;
        continue;
    }

    //check for compliance issues
    if($count = substr_count(strtolower($page), strtolower($keyword))){
        //alert someone if set
        if(isset($getopt['a'])){
            error_log('alerting user');
            $nexmo->message()->send(new \Nexmo\Message\Text(
                $getopt['a'],
                $config['nexmo']['from'],
                'A compliance issue was found: ' . $crawl
            ));
        }

        $found[] = $crawl;
        error_log('found keyword `' . $keyword . '` on page: ' . $crawl);
    }

    $crawled[] = $crawl;

    //find links to crawl
    foreach($xml->xpath('//a') as $link){
        $url = (string) $link['href'];
        $host = parse_url($url, PHP_URL_HOST);

        if(!$host){
            continue;
        }

        //restrict to host
        if(false === strpos($host, $domain)){
            continue;
        }

        //only crawl a url once
        if(in_array($url, $crawled)){
            continue;
        }

        //only follow a url once
        if(in_array($url, $follow)){
            continue;
        }

        $follow[] = $url;
    }

    error_log(count($follow) . ' pages to crawl');
    error_log('using ' . round(memory_get_usage()/1048576,2) . ' megabytes of memory');

} while (!empty($follow));

echo "found URLs:" . PHP_EOL;
foreach($found as $url){
    echo $url . PHP_EOL;
}