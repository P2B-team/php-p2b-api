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

$response = $api->accountBalances();
print_r($response . PHP_EOL);