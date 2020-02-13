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
$orderId = 1;

$response = $api->cancelOrder($market, $orderId);
print_r($response . PHP_EOL);