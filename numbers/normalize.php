<?php
//autoloading and config
require_once '../vendor/autoload.php';
$config = include '../config.php';

$nexmo = new Nexmo\Client(new \Nexmo\Client\Credentials\Basic($config['nexmo']['key'], $config['nexmo']['secret']));

while($row = fgetcsv(STDIN)){
    $request = new \Zend\Diactoros\Request(
        'https://api.nexmo.com/ni/basic/json',
        'POST',
        'php://temp',
        ['Content-Type' => 'application/json']
    );

    $request->getBody()->write(json_encode([
        'country' => $row[0],
        'number' => $row[1]
    ]));

    $response = $nexmo->send($request);
    $data = $response->getBody()->getContents();
    $data = json_decode($data, true);

    if(!$data OR !isset($data['status']) OR !($data['status'] == 0)){
        fputcsv(STDOUT, array_merge($row, [null, null]));
        continue;
    }

    fputcsv(STDOUT, array_merge($row, [
        $data['international_format_number'],
        $data['national_format_number']
    ]));
}