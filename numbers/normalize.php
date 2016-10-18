#!/usr/local/bin/php
<?php
//autoloading and config
require_once __DIR__ . '/../vendor/autoload.php';
$config = include __DIR__ . '/../config.php';

//setup nexmo
$nexmo = new Nexmo\Client(new \Nexmo\Client\Credentials\Basic($config['nexmo']['key'], $config['nexmo']['secret']));

$count = 0;
$animation = '/-\|';

//setup signls
pcntl_signal(SIGINT, function() use (&$count){
    fwrite(STDERR, PHP_EOL);
    fwrite(STDERR, "processed $count lines" . PHP_EOL);
    fwrite(STDERR, "use `-r $count` to resume" . PHP_EOL);
    exit;
});
declare(ticks=1);

//support resume
$getopt = getopt('r:');
if(isset($getopt['r']) AND $getopt['r']){
    for($l = 0; $l < $getopt['r']; $l++){
        fgets(STDIN);
    }
    $count = $getopt['r'];
    fwrite(STDERR, "resuming from line $count" . PHP_EOL);
}

//catch any error, to allow resume
try{
    while($row = fgetcsv(STDIN)){

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

        //no number data found
        if(!$data OR !isset($data['status']) OR !($data['status'] == 0)){
            fputcsv(STDOUT, array_merge($row, [null, null]));
            continue;
        }

        //number data found
        fputcsv(STDOUT, array_merge($row, [
            $data['international_format_number'],
            $data['national_format_number']
        ]));
        $count++;

        //output status
        $status = "processing line $count " . $animation[$count % 4] . ' ';
        if(isset($last)){
            fwrite(STDERR, str_pad('', strlen($last), chr(8)));
        }

        fwrite(STDERR, $status);
        $last = $status;
    }
} catch (Exception $e){
    //send ourself SIGINT
    error_log($e->getMessage());
    posix_kill(posix_getpid(), SIGINT);
}
