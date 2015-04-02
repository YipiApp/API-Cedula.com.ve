<?php
/**
* MercadoPago Integration Library, Access MercadoPago for payments integration.
* 
* @author    hcasatti
* @copyright 2007-2011 MercadoPago
* @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

$GLOBALS["LIB_LOCATION_VE"] = dirname(__FILE__);

class MercadoPagoVE {

    const version = "0.1.9";

    private $client_id;
    private $client_secret;
    private $access_data;
    private $sandbox = FALSE;

    function __construct($client_id, $client_secret) {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
    }

    public function sandbox_mode($enable = NULL) {
        if (!is_null($enable)) {
            $this->sandbox = $enable === TRUE;
        }

        return $this->sandbox;
    }

    /**
     * Get Access Token for API use
     */
    public function get_access_token() {
        $appClientValues = $this->build_query(array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'client_credentials'
                ));

        $access_data = MPRestClientVe::post("/oauth/token", $appClientValues, "application/x-www-form-urlencoded");

        $this->access_data = $access_data['response'];

        return $this->access_data['access_token'];
    }

    /**
     * Get information for specific payment
     * @param int $id
     * @return array(json)
     */
    public function get_payment_info($id) {
        $accessToken = $this->get_access_token();

        $uriPrefix = $this->sandbox ? "/sandbox" : "";
            
        $paymentInfo = MPRestClientVe::get($uriPrefix."/collections/notifications/" . $id . "?access_token=" . $accessToken);
        return $paymentInfo;
    }

    /**
     * Refund accredited payment
     * @param int $id
     * @return array(json)
     */
    public function refund_payment($id) {
        $accessToken = $this->get_access_token();

        $refund_status = array(
            "status" => "refunded"
        );

        $response = MPRestClientVe::put("/collections/" . $id . "?access_token=" . $accessToken, $refund_status);
        return $response;
    }

    /**
     * Cancel pending payment
     * @param int $id
     * @return array(json)
     */
    public function cancel_payment($id) {
        $accessToken = $this->get_access_token();

        $cancel_status = array(
            "status" => "cancelled"
        );

        $response = MPRestClientVe::put("/collections/" . $id . "?access_token=" . $accessToken, $cancel_status);
        return $response;
    }

    /**
     * Search payments according to filters, with pagination
     * @param array $filters
     * @param int $offset
     * @param int $limit
     * @return array(json)
     */
    public function search_payment($filters, $offset = 0, $limit = 0) {
        $accessToken = $this->get_access_token();

        $filters["offset"] = $offset;
        $filters["limit"] = $limit;

        $filters = $this->build_query($filters);

        $uriPrefix = $this->sandbox ? "/sandbox" : "";
            
        $collectionResult = MPRestClientVe::get($uriPrefix."/collections/search?" . $filters . "&access_token=" . $accessToken);
        return $collectionResult;
    }

    /**
     * Create a checkout preference
     * @param array $preference
     * @return array(json)
     */
    public function create_preference($preference) {
        $accessToken = $this->get_access_token();

        $preferenceResult = MPRestClientVe::post("/checkout/preferences?access_token=" . $accessToken, $preference);
        return $preferenceResult;
    }

    /**
     * Update a checkout preference
     * @param string $id
     * @param array $preference
     * @return array(json)
     */
    public function update_preference($id, $preference) {
        $accessToken = $this->get_access_token();

        $preferenceResult = MPRestClientVe::put("/checkout/preferences/{$id}?access_token=" . $accessToken, $preference);
        return $preferenceResult;
    }

    /**
     * Get a checkout preference
     * @param string $id
     * @return array(json)
     */
    public function get_preference($id) {
        $accessToken = $this->get_access_token();

        $preferenceResult = MPRestClientVe::get("/checkout/preferences/{$id}?access_token=" . $accessToken);
        return $preferenceResult;
    }

    /*     * **************************************************************************************** */

    private function build_query($params) {
        if (function_exists("http_build_query")) {
            return http_build_query($params);
        } else {
            foreach ($params as $name => $value) {
                $elements[] = "{$name}=" . urlencode($value);
            }

            return implode("&", $elements);
        }
    }

}

/**
 * MercadoPago cURL RestClient
 */
class MPRestClientVe {

    const API_BASE_URL = "https://api.mercadopago.com";

    private static function getConnect($uri, $method, $contentType) {
        $connect = curl_init(self::API_BASE_URL . $uri);

        curl_setopt($connect, CURLOPT_USERAGENT, "MercadoPago PHP SDK v" . MercadoPagoVE::version);
        //curl_setopt($connect, CURLOPT_CAINFO, $GLOBALS["LIB_LOCATION_VE"] . "/cacert.pem");
        //curl_setopt($connect, CURLOPT_SSLVERSION, 3);
        curl_setopt($connect, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($connect, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($connect, CURLOPT_FAILONERROR, false);  
		curl_setopt($connect, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($connect, CURLOPT_HTTPHEADER, array("Accept: application/json", "Content-Type: " . $contentType));

        return $connect;
    }

    private static function setData(&$connect, $data, $contentType) {
        if ($contentType == "application/json") {
            if (gettype($data) == "string") {
                json_decode($data, true);
            } else {
                $data = json_encode($data);
            }

            if(function_exists('json_last_error')) {
                $json_error = json_last_error();
                if ($json_error != JSON_ERROR_NONE) {
                    throw new Exception("JSON Error [{$json_error}] - Data: {$data}");
                }
            }
        }

        curl_setopt($connect, CURLOPT_POSTFIELDS, $data);
    }

    private static function exec($method, $uri, $data, $contentType) {
        $connect = self::getConnect($uri, $method, $contentType);
        if ($data) {
            self::setData($connect, $data, $contentType);
        }

        $apiResult = curl_exec($connect);
        $apiHttpCode = curl_getinfo($connect, CURLINFO_HTTP_CODE);

        $response = array(
            "status" => $apiHttpCode,
            "response" => json_decode($apiResult, true)
        );

        if ($response['status'] >= 400) {
            throw new Exception ($response['response']['message'], $response['status']);
        }

        curl_close($connect);

        return $response;
    }

    public static function get($uri, $contentType = "application/json") {
        return self::exec("GET", $uri, null, $contentType);
    }

    public static function post($uri, $data, $contentType = "application/json") {
        return self::exec("POST", $uri, $data, $contentType);
    }

    public static function put($uri, $data, $contentType = "application/json") {
        return self::exec("PUT", $uri, $data, $contentType);
    }

}

?>
