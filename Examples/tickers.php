<?php

require_once '../Api.php';
require_once '../vendor/autoload.php';

/*
 *  The endpoint of the public part of the API.
 *
 * */

$api = new P2pb2b\Api();

$response = $api->tickers();
print_r($response . PHP_EOL);