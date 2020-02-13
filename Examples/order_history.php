<?php

require_once '../Api.php';
require_once '../vendor/autoload.php';

/*
 *  The endpoint of the public part of the API.
 *
 * */

$api = new P2pb2b\Api();

$market = 'ETH_BTC';
$lastId = 1;

$response = $api->history($market, (int) $lastId);
print_r($response . PHP_EOL);