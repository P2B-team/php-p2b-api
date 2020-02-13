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

$currency = 'ETH';

$response = $api->accountBalance($currency);
print_r($response . PHP_EOL);