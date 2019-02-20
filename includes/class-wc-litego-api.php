<?php
if (!defined('ABSPATH')) {
	exit;
}

class WC_Litego_Api {

    const LITEGO_MAINNET_URL = 'https://api.litego.io:9000';
    const LITEGO_TESTNET_URL = 'https://sandbox.litego.io:9000';

    const LITEGO_MAINNET_MODE = "live";
    const LITEGO_TESTNET_MODE = "test";

    const AUTHENTICATE_API_URL              = '/api/v1/merchant/authenticate';
    const REFRESHTOKEN_API_URL              = '/api/v1/merchant/me/refresh-auth';
    const CHARGES_API_URL                   = '/api/v1/charges';
    const MERCHANT_API_URL                  = '/api/v1/merchant/me';
    const WITHDRAWAL_SET_API_URL            = '/api/v1/merchant/me/withdrawal/address';
    const WITHDRAWAL_TRIGGER_API_URL        = '/api/v1/merchant/me/withdrawal/manual';
    const WITHDRAWAL_LIST_API_URL           = '/api/v1/merchant/me/withdrawals';
    const WEBHOOK_SET_URL_API_URL           = '/api/v1/merchant/me/notification-url';
    const WEBHOOK_LIST_RESPONSES_API_URL    = '/api/v1/merchant/me/notification-responses';

    const TIMEOUT = 10;

    //codes
    const CODE_200 = 200;
    const CODE_400 = 400;

    /**
     * @var string
     */
    protected $serviceUrl;

    function __construct($mode = self::LITEGO_MAINNET_MODE) {
        $this->serviceUrl = self::LITEGO_MAINNET_URL;

        if ($mode == self::LITEGO_TESTNET_MODE) {
            $this->serviceUrl = self::LITEGO_TESTNET_URL;
        }
    }

    /**
     * Refresh auth_token with refresh_token, if failed (refresh_token is expired) try to refresh refresh_token with secret_key
     *
     * @param string    $refreshToken   Refresh token to refresh temporary auth key
     * @param string    $merchantId     Merchant ID
     * @param string    $secretKey      Secret Key
     * @param int       $timeout
     * @return array    Array of JWT authentication: auth_token and refresh_token
     * @throws Exception
     */
    public function reauthenticate($refreshToken = "", $merchantId = "", $secretKey = "",  $timeout = self::TIMEOUT) {

        if (!$refreshToken) {
            //request to get new auth and refresh token with secret key
            $result = $this->authenticate($merchantId, $secretKey, $timeout);

            if ($result['error']) {
                throw new Exception('Litego API: ' . $result['error_message']);
            }

            return array(
                'auth_token' => $result['auth_token'],
                'refresh_token' => $result['refresh_token']
            );

        }
        else {
            //request for refresh auth token with refresh token
            $result = $this->refreshAuthToken($refreshToken, $timeout);

            if ($result['error'] && $result['error_name'] == "Forbidden") {
                //try to get new auth and refresh token with secret key
                return $this->reauthenticate("", $merchantId, $secretKey, $timeout);
            }

            if ($result['error']) {
                throw new Exception('Litego API: ' . $result['error_message']);
            }

            return array(
                'auth_token' => $result['auth_token'],
                'refresh_token' => $refreshToken
            );
        }
    }

    /**
     * Calls to the API are authenticated with secret API Key and merchant API ID,
     * which you can find in your account settings on litego.io
     *
     * @param $merchantId   Merchant ID
     * @param $secretKey    Secret key
     * @param int $timeout
     * @return array
     */
    public function authenticate($merchantId, $secretKey, $timeout = self::TIMEOUT) {
        $data['merchant_id'] = $merchantId;
        $data['secret_key'] = $secretKey;

        $result = $this->doApiRequest(self::AUTHENTICATE_API_URL, 'POST', $data, array(), $timeout);

        if ($result['response_result']) {
            $result['response_result'] = json_decode($result['response_result'],1);
        }

        if ($result['response_code'] == self::CODE_200) {
            return array(
                'code' => self::CODE_200,
                'auth_token' => $result['response_result']['auth_token'],
                'refresh_token' => $result['response_result']['refresh_token'],
                'error' => 0
            );
        }
        else {
            return array(
                'code' => $result['response_code'] ? $result['response_code'] : self::CODE_400,
                'error' => 1,
                'error_name' => $result['response_result']['name'],
                'error_message' => $result['response_result']['detail'],
            );
        }
    }

    /**
     * Refresh auth_token with refresh_token. Refresh_token is inserted into Authorization request header.
     * When auth_token lifetime is over, all other API requests return authorization error
     *
     * @param string    $refreshToken   Refresh token key (is returned with authentication)
     * @param int       $timeout        Request timeout
     * @return array    Assoc array of decoded result
     */
    public function refreshAuthToken($refreshToken, $timeout = self::TIMEOUT) {
        //authorization headers
        $headers = array(
            'Authorization' => 'Bearer ' . $refreshToken
        );

        $result = $this->doApiRequest(self::REFRESHTOKEN_API_URL, 'PUT', $data = array(), $headers, $timeout);

        if ($result['response_result']) {
            $result['response_result'] = json_decode($result['response_result'],1);
        }

        if ($result['response_code'] == self::CODE_200) {
            return array(
                'code' => self::CODE_200,
                'auth_token' => $result['response_result']['auth_token'],
                'error' => 0
            );
        }
        else {
            return array(
                'code' => $result['response_code']?$result['response_code']:self::CODE_400,
                'error' => 1,
                'error_name' => $result['response_result']['name'],
                'error_message' => $result['response_result']['detail'],
            );
        }
    }

    /**
     * Create a new charge when a payment is required
     *
     * @param string    $authToken      Authentication key
     * @param string    $description    Charge description
     * @param int       $amount_satoshi Amount
     * @param int       $timeout
     * @return array
     */
    public function createCharge($authToken, $description = "", $amount_satoshi = 0, $timeout = self::TIMEOUT) {
        //authentication headers
        $headers = array(
            'Authorization' => 'Bearer ' . $authToken
        );

        //prepare mandatory params for request
        $data = array(
            "description" => $description,
            "amount_satoshi" => $amount_satoshi
        );

        $result = $this->doApiRequest(self::CHARGES_API_URL, 'POST', $data, $headers, $timeout);

        if ($result['response_result']) {
            $result['response_result'] = json_decode($result['response_result'],1);
        }

        if ($result['response_code'] == self::CODE_200) {
            return array(
                'code' => self::CODE_200,
                'id' => $result['response_result']['id'],
                'merchant_id' => $result['response_result']['merchant_id'],
                'description' => $result['response_result']['description'],
                'amount' => $result['response_result']['amount'],
                'amount_satoshi' => $result['response_result']['amount_satoshi'],
                'payment_request' => $result['response_result']['payment_request'],
                'paid' => $result['response_result']['paid'],
                'created' => $result['response_result']['created'],
                'expiry_seconds' => $result['response_result']['expiry_seconds'],
                'object' => $result['response_result']['object'],
                'error' => 0
            );
        }
        else {
            return array(
                'code' => $result['response_code'] ? $result['response_code'] : self::CODE_400,
                'error' => 1,
                'error_name' => $result['response_result']['name'],
                'error_message' => $result['response_result']['detail'],
            );
        }
    }

    /**
     * @param string    $authToken      Authentication key
     * @param string    $chargeId       Charge ID
     * @param int       $timeout
     * @return array
     */
    public function getCharge($authToken, $chargeId, $timeout = self::TIMEOUT) {
        $headers = array(
            'Authorization' => 'Bearer ' . $authToken
        );

        $result = $this->doApiRequest(self::CHARGES_API_URL . "/" . $chargeId, 'GET', array(), $headers, $timeout);

        if ($result['response_result']) {
            $result['response_result'] = json_decode($result['response_result'],1);
        }


        if ($result['response_code'] == self::CODE_200) {
            return array(
                'code' => self::CODE_200,
                'id' => $result['response_result']['id'],
                'merchant_id' => $result['response_result']['merchant_id'],
                'description' => $result['response_result']['description'],
                'amount' => $result['response_result']['amount'],
                'amount_satoshi' => $result['response_result']['amount_satoshi'],
                'amount_paid_satoshi' => $result['response_result']['amount_paid_satoshi'],
                'payment_request' => $result['response_result']['payment_request'],
                'paid' => $result['response_result']['paid'],
                'created' => $result['response_result']['created'],
                'expiry_seconds' => $result['response_result']['expiry_seconds'],
                'object' => $result['response_result']['object'],
                'error' => 0
            );
        }
        else {
            return array(
                'code' => $result['response_code'] ? $result['response_code'] : self::CODE_400,
                'error' => 1,
                'error_name' => $result['response_result']['name'],
                'error_message' => $result['response_result']['detail'],
            );
        }
    }

    /**
     * Perform the HTTP request.
     *
     * @param $apiPart      API method to be called
     * @param $method       HTTP method to use: get, post
     * @param array $data   Assoc array of parameters to be passed
     * @param int $timeout  Request timeout
     *
     * @return array        Result array
     */
    private function doApiRequest($apiPart, $method, $data = array(), $headers = array(), $timeout = self::TIMEOUT)
    {
        try {
            //API Service url to be called
            $url = $this->serviceUrl . $apiPart;

            //default header options
            $httpHeaders = array(
                'Content-Type' => 'application/json'
            );

            if (is_array($headers) && count($headers) > 0) {
                $httpHeaders = array_merge($httpHeaders, $headers);
            }

            switch ($method) {
                case 'POST':
                    $response = wp_remote_request(
                        $url,
                        array(
                            'method'     => 'POST',
                            'headers'    => $httpHeaders,
                            'body'       => json_encode($data),
                            'timeout'    => $timeout, // in seconds
                            'user-agent' => 'WooCommerce ' . WC()->version,
                        )
                    );
                    break;
                case 'GET':
                    $query = http_build_query($data, '', '&');
                    $response = wp_remote_get(
                        $url . '?' . $query,
                        array(
                            'headers'    => $httpHeaders,
                            'timeout'    => $timeout, // in seconds
                            'user-agent' => 'WooCommerce ' . WC()->version
                        )
                    );
                    break;
                case 'PUT':
                    $response = wp_remote_request(
                        $url,
                        array(
                            'method'     => 'PUT',
                            'headers'    => $httpHeaders,
                            'body'       => json_encode($data),
                            'timeout'    => $timeout, // in seconds
                            'user-agent' => 'WooCommerce ' . WC()->version
                        )
                    );
                    break;
            }

            $responseCode = wp_remote_retrieve_response_code($response);
            $responseContent = $response['body'];

            return array(
                'response_code' => $responseCode,
                'response_result' => $responseContent,
            );

        } catch (Exception $e) {
            return array(
                'response_code' => $e->getCode(),
                'response_error' => $e->getMessage()
            );
        }
    }
}
