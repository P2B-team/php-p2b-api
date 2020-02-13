<?php

require_once '../Api.php';
require_once '../vendor/autoload.php';

/*
 *  The endpoint of the private part of the API.
 *  You must configure the settings using one of the available methods.
 *  See README.md
 *
 * */

$api = new P2pb2b\Api();

$market = 'ETH_BTC';
$side = 'sell';
$amount = '0.001';
$price = '100000.00';


$response = $api->createOrder($market, $side, $amount, $price);
print_r($response . PHP_EOL);