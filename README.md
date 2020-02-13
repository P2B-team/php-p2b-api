# php-p2pb2b-api


**api documentation:**  <https://github.com/P2pb2b-team/p2pb2b-api-docs>


To access the private API, you must have access setting parameters: **KEY API**, **API SECRET**. And other conditions for working with the private API are met.

To work with the private part of the api project, you need:

* Register and pass the verification.
* Set two-factor verification.
* Generate and activate API access parameters.


## Setting access parameters

Set access parameters in one of the ways.

### .env

Set your parameters API KEY, API SECRET

P2PB2B_API_KEY=api key

P2PB2B_API_SECRET=secret

### php-p2pb2b-api-config.json

Сreate the php-p2pb2b-api-config.json file from the php-p2pb2b-api-config.json.example file in the project root.

Set your parameters  API KEY, API SECRET 

```$json
{
    "api-key": "<api key>",
    "api-secret": "<secret>"
}
```

### Api constructor

$api = new P2pb2b\Api($apiKey, $secret);

## Examples

Path to examples:  path/to/the/api/module/Examples.

Access to public endpoints is possible without registering and setting authentication parameters for the api module.

To access the private part of the API, you must have an account on the resource with the created and active access authentication parameters.
Connect the parameters in one of the above ways.

The examples use random parameters.

* Be careful when performing non-informational endpoints. For example, creating or canceling an order.

When transmitting incorrect or non-existent data in a request, you can see a module validation error or an unsuccessful api response.

Api response errors can be found in the documentation: <https://github.com/P2pb2b-team/p2pb2b-api-docs/blob/master/errors.md>
