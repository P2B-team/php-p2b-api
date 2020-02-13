<?php

namespace P2pb2b;

use Exceptions\ApiConstructorException;
use Exceptions\AuthenticationException;
use Exceptions\ResponseException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Exception;

/**
 * Class Api
 *
 * @property Client client
 */
class Api
{
    public const SHA512 = 'sha512';
    protected const ENDPOINT_PREFIX = '/api/v2';
    const BASE_URI = 'https://api.p2pb2b.io/';
    const TIMEOUT = 10.0;

    public $api_key = '';
    public $api_secret = '';

    public const SIDE_SELL = 'sell';
    public const SIDE_BUY = 'buy';
    public const SIDE_ARRAY = [
        self::SIDE_SELL,
        self::SIDE_BUY,
    ];

    public const INTERVAL_1M = '1m';
    public const INTERVAL_1H = '1h';
    public const INTERVAL_1D = '1d';
    public const KLINE_INTERVAL_ARRAY = [
        self::INTERVAL_1M,
        self::INTERVAL_1H,
        self::INTERVAL_1D,
    ];
    public const DEPTH_INTERVAL_ARRAY = [
        0, 0.1, 0.01, 0.001, 0.0001, 0.00001, 0.000001, 0.0000001, 0.00000001, 1,
    ];

    /**
     * Constructor for the class,
     *
     * No arguments - set P2PB2B_API_KEY and P2PB2B_API_SECRET in environment
     * 2 arguments - api key and api secret
     *
     * @return null
     */
    public function __construct()
    {
        $param = func_get_args();
        switch (count($param)) {
            case 0:
                $this->setupApiConfigFromEnv();
                $this->setupApiConfigFromFile();
                break;
            case 1:
                $this->setupApiConfigFromFile($param[0]);
                break;
            case 2:
                $this->api_key = $param[0];
                $this->api_secret = $param[1];
                break;
            default:
                throw new ApiConstructorException('Invalid constructor parameters.');
        }
        $this->client = new Client(['base_uri' => self::BASE_URI, 'timeout' => self::TIMEOUT]);
    }


    protected function setupApiConfigFromEnv(): void
    {
        $apiKey = getenv('P2PB2B_API_KEY');
        $apiSecret = getenv('P2PB2B_API_SECRET');
        if ($apiKey && $apiSecret) {
            $this->api_key = $apiKey;
            $this->api_secret = $apiSecret;
        }
    }


    private function setupApiConfigFromFile(string $file = null): void
    {
        $file = is_null($file) ? __DIR__ . "/php-p2pb2b-api-config.json" : $file;

        if (empty($this->api_key) === false || empty($this->api_key) === false) {
            return;
        }
        if (file_exists($file) === false) {
            return;
        }
        $contents = json_decode(file_get_contents($file), true);
        $this->api_key = $contents['api-key'] ?? "";
        $this->api_secret = $contents['api-secret'] ?? "";
    }

    protected function publicRequest(string $endpoint, array $arguments = []): string
    {
        $params[RequestOptions::QUERY] = $arguments;

        $endpoint = self::ENDPOINT_PREFIX . $endpoint;

        return $this->sendRequest(
            'GET',
            $endpoint,
            $params
        );
    }

    protected function privateRequest(string $endpoint, array $arguments = []): string
    {
        if (!$this->api_key || !$this->api_secret) {
            throw new  AuthenticationException('API KEY or API SECRET do not exist in the environment, all private requests will not be executed');
        }

        $endpoint = self::ENDPOINT_PREFIX . $endpoint;

        $body = array_merge($arguments, [
            'request' => $endpoint,
            'nonce' => microtime(true) * 1000
        ]);
        $params[RequestOptions::JSON] = $body;
        $params[RequestOptions::HEADERS] = $this->getPrivateHeaders($body);

        return $this->sendRequest(
            'POST',
            $endpoint,
            $params
        );
    }

    protected function sendRequest(
        $method,
        $endpoint,
        array $params = []
    ): string {
        try {
            $response = $this->client->request($method, $endpoint, $params);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if ($response === null) {
                throw new ResponseException($e->getMessage(), $e->getCode());
            }
        }

        return $response->getBody()->getContents();
    }

    private function getPrivateHeaders(array $body): array
    {
        $payload = base64_encode(json_encode($body, JSON_UNESCAPED_SLASHES));

        return [
            'X-TXC-APIKEY' => $this->api_key,
            'X-TXC-PAYLOAD' => $payload,
            'X-TXC-SIGNATURE' => hash_hmac(self::SHA512, $payload, $this->api_secret),
        ];
    }

    /**
     * Get info on all markets.
     *
     * @return string
     * @throws \Exception
     */
    public function markets(): string
    {
        $endpoint = '/public/markets';

        return $this->publicRequest($endpoint);
    }

    /**
     * Get info by market.
     *
     * @param $market , string, required, market name from the list of existing included markets
     * @return string
     * @throws \Exception
     */
    public function market(string $market): string
    {
        $endpoint = '/public/market';

        return $this->publicRequest($endpoint, ['market' => $market]);
    }

    /**
     * Get trade details for all tickers.
     *
     * @return string
     * @throws \Exception
     */
    public function tickers(): string
    {
        $endpoint = '/public/tickers';

        return $this->publicRequest($endpoint);
    }

    /**
     * Get trade details for a ticker.
     *
     * @param $market , string, required, market name from the list of existing included markets
     * @return string
     * @throws \Exception
     */
    public function ticker(string $market): string
    {
        $endpoint = '/public/ticker';

        return $this->publicRequest($endpoint, ['market' => $market]);
    }

    /**
     * Get all unexecuted orders by market.
     *
     * @param $market , string, required, market name from the list of existing included markets
     * @param $side , string, required, valid list: sell, buy
     * @param $limit , int, not required, , min 1, default 50
     * @param $offset , int, not required, min 0, default 0
     * @return string
     * @throws \Exception
     */
    public function book(string $market, string $side, int $limit = 50, int $offset = 0): string
    {
        if (in_array($side, self::SIDE_ARRAY) === false) {
            $sides = implode(",", self::SIDE_ARRAY);
            throw new Exception('Invalid interval value. Value should be from the list: ' . $sides);
        }

        $endpoint = '/public/book';

        return $this->publicRequest($endpoint, ['market' => $market, 'side' => $side, 'limit' => $limit, 'offset' => $offset]);
    }

    /**
     * Each order history starts with order ID.
     *
     * @param $market , string, required, market name from the list of existing included markets
     * @param $lastId , int, required, executed order id
     * @param $limit , int, not required, , min 1, default 50
     * @return string
     * @throws \Exception
     */
    public function history(string $market, int $lastId, int $limit = 50): string
    {
        $endpoint = '/public/public/history';

        return $this->publicRequest($endpoint, ['market' => $market, 'lastId' => $lastId, 'limit' => $limit]);
    }

    /**
     * Order depth for a market.
     *
     * @param $market , string, required, market name from the list of existing included markets
     * @param $interval , not required, one of the list: 0, 0.00000001, 0.0000001, "0.000001, 0.00001, 0.0001, 0.001, 0.01, 0.1, 1. Default 0;
     * @param $limit , int, not required, min 1, default 50
     * @return string
     * @throws \Exception
     */
    public function depth(string $market, float $interval = 0.0, int $limit = 50): string
    {
        if (in_array($interval, self::DEPTH_INTERVAL_ARRAY) === false) {
            $intervals = implode(",", self::DEPTH_INTERVAL_ARRAY);
            throw new Exception('Invalid interval value. Value should be from the list: ' . $intervals);
        }

        $endpoint = '/public/depth/result';

        return $this->publicRequest($endpoint, ['market' => $market, 'interval' => $interval, 'limit' => $limit]);
    }

    /**
     * Kline/candlestick bars for a market. Kline identification takes place at the opening time.
     *
     * @param $market , string, required, market name from the list of existing included markets
     * @param $interval , string, required, Name of the interval from the list of valid intervals : 1m, 1h, 1d
     * @param $limit , int, not required, , min 1, default 50
     * @param $offset , int, not required, min 0, default 0
     * @return string
     * @throws \Exception
     */
    public function kline(string $market, string $interval, int $limit = 50, int $offset = 0): string
    {
        if (in_array($interval, self::KLINE_INTERVAL_ARRAY) === false) {
            $intervals = implode(",", self::KLINE_INTERVAL_ARRAY);
            throw new Exception('Invalid interval value. Value should be from the list: ' . $intervals);
        }

        $endpoint = '/public/market/kline';

        return $this->publicRequest($endpoint, ['market' => $market, 'interval' => $interval, 'limit' => $limit, 'offset' => $offset]);
    }

    /**
     * List of user balances for all currencies.
     *
     * @return string
     * @throws \Exception
     */
    public function accountBalances(): string
    {
        $endpoint = '/account/balances';

        return $this->privateRequest($endpoint);
    }

    /**
     * User balance for the selected currency.
     *
     * @param $currency , string, required, currency from the list of existing included currencies
     * @return string
     * @throws \Exception
     */
    public function accountBalance(string $currency): string
    {
        $endpoint = '/account/balance';

        return $this->privateRequest($endpoint, ['currency' => $currency]);
    }

    /**
     * Query executed orders.
     *
     * @return string
     * @throws \Exception
     */
    public function accountOrderHistory(): string
    {
        $endpoint = '/account/order_history';

        return $this->privateRequest($endpoint);
    }

    /**
     * Query deal details by executed order ID.
     *
     * @param $orderId
     * @param $limit , int, not required, , min 1, default 50
     * @param $offset , int, not required, min 0, default 0
     * @return string
     * @throws \Exception
     */
    public function accountOrderDeals(int $orderId, int $limit = 50, int $offset = 0): string
    {
        $endpoint = '/account/order';

        return $this->privateRequest($endpoint, ['orderId' => $orderId, 'limit' => $limit, 'offset' => $offset]);
    }

    /**
     * List of executed user orders for a market.
     *
     * @param $market , string, required, market name from the list of existing included markets
     * @param $limit , int, not required, , min 1, default 50
     * @param $offset , int, not required, min 0, default 0
     * @return string
     * @throws \Exception
     */
    public function accountExecutedHistory(string $market, int $limit = 50, int $offset = 0): string
    {
        $endpoint = '/account/executed_history';

        return $this->privateRequest($endpoint, ['market' => $market, 'limit' => $limit, 'offset' => $offset]);
    }

    /**
     * List of executed user orders for all markets.
     *
     * @param $limit , int, not required, , min 1, default 50
     * @param $offset , int, not required, min 0, default 0
     * @return string
     * @throws \Exception
     */
    public function accountAllExecutedHistory(int $limit = 50, int $offset = 0): string
    {
        $endpoint = '/account/executed_history/all';

        return $this->privateRequest($endpoint, ['limit' => $limit, 'offset' => $offset]);
    }

    /**
     * Query unexecuted orders.
     *
     * @param $market , string, required, market name from the list of existing included markets
     * @param $limit , int, not required, , min 1, default 50
     * @param $offset , int, not required, min 0, default 0
     * @return string
     * @throws \Exception
     */
    public function orders(string $market, int $limit = 50, int $offset = 0): string
    {
        $endpoint = '/orders';

        return $this->privateRequest($endpoint, ['market' => $market, 'limit' => $limit, 'offset' => $offset]);
    }

    /**
     * Сreating an order for a trade.
     *
     * @param $market , string, required, market name from the list of existing included markets
     * @param $side , string, required, valid list: sell, buy
     * @param $amount string, required, amount numeric string
     * @param $price string, required, price numeric string
     * @return string
     * @throws \Exception
     */
    public function createOrder(string $market, string $side, string $amount, string $price): string
    {
        if (in_array($side, self::SIDE_ARRAY) === false) {
            $sides = implode(",", self::SIDE_ARRAY);
            throw new Exception('Invalid interval value. Value should be from the list: ' . $sides);
        }

        $endpoint = '/order/new';

        return $this->privateRequest($endpoint, ['market' => $market, 'side' => $side, 'amount' => $amount, 'price' => $price]);
    }

    /**
     * Cancel an active order for a market by order ID.
     *
     * @param $market , string, required, market name from the list of existing included markets
     * @param $orderId , int, required, cancel order id
     * @return string
     * @throws \Exception
     */
    public function cancelOrder(string $market, int $orderId): string
    {
        $endpoint = '/order/cancel';

        return $this->privateRequest($endpoint, ['market' => $market, 'orderId' => $orderId]);
    }

    /**
     * Cancel all active orders for the 'market'.
     *
     * @param $market , string, required, market name from the list of existing included markets
     * @return string
     * @throws \Exception
     */
    public function cancelAllOrders(string $market): string
    {
        $endpoint = '/order/cancel/all';

        return $this->privateRequest($endpoint, ['market' => $market]);
    }

}
