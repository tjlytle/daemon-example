#!/usr/local/bin/php
<?php
//autoloading and config
require_once __DIR__ . '/../vendor/autoload.php';
$config = include __DIR__ . '/../config.php';

//setup nexmo
$nexmo = new Nexmo\Client(new \Nexmo\Client\Credentials\Basic($config['nexmo']['key'], $config['nexmo']['secret']));

//process all the rows
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
}