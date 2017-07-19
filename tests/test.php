<?php

require __DIR__ . '/../vendor/autoload.php';


use Godruoyi\PrettySms\Client;

$client = new Client(__DIR__ . '/../src/config.php');


$x = $client->send();

dump($x);