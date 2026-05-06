<?php

namespace dbsnOOp\Utils;

use dbsnOOp\DSSegment;
use Firebase\JWT\JWT;

final class Request
{

    const DEFAULT_TIMEOUT = 2000;
    const DEFAULT_CONNECTION_TIMEOUT = 2000;

    private string $_uri;
    private string $_hash;
    private string $_token;

    private static array $_promises = [];
    private static ?\GuzzleHttp\Client $_client = null;

    public function __construct()
    {

        if (!getenv('DBSNOOP_APM_APP_KEY')) {
            trigger_error("The 'DBSNOOP_APM_APP_KEY' is not defined in dbsnoop.ini file", E_USER_WARNING);
            return false;
        }

        if (!getenv('DBSNOOP_APM_HOST_URL')) {
            trigger_error("The 'DBSNOOP_APM_HOST_URL' is not defined in dbsnoop.ini file", E_USER_WARNING);
            return false;
        }

        if (!getenv('DBSNOOP_APM_APP_TOKEN')) {
            trigger_error("The 'DBSNOOP_APM_APP_TOKEN' is not defined in dbsnoop.ini file", E_USER_WARNING);
            return false;
        }

        $this->_hash = getenv('DBSNOOP_APM_APP_KEY');
        $this->_uri = getenv('DBSNOOP_APM_HOST_URL');
        $this->_token = getenv('DBSNOOP_APM_APP_TOKEN');
    }


    public function send(DSSegment $segment)
    {
        $payload = $segment->getStructure();
        $this->request($payload);
    }

    private function request(array $payload)
    {
        if (empty($this->_uri) || empty($this->_token) || empty($this->_hash)) {
            return;
        }

        try {
            if (self::$_client === null) {
                self::$_client = new \GuzzleHttp\Client([
                    'timeout' => self::DEFAULT_TIMEOUT / 1000.0,
                    'connect_timeout' => self::DEFAULT_CONNECTION_TIMEOUT / 1000.0,
                    'allow_redirects' => true,
                    'verify' => false,
                ]);
            }

            $body = $this->getBody($payload);

            Logger::get()->debug("Queueing Segment - Host: " . $this->_uri . " - Payload: " . json_encode($payload));

            if (PHP_SAPI === 'cli') {
                self::$_promises[] = self::$_client->postAsync('https://' . $this->_uri . "/v2/apm/send", [
                    'headers' => $this->getHeaders(),
                    'body' => $body,
                ]);
            } else {
                // Em ambiente FPM/Web, enviamos síncrono para garantir envio e logs antes da desconexão
                try {
                    $response = self::$_client->post('https://' . $this->_uri . "/v2/apm/send", [
                        'headers' => $this->getHeaders(),
                        'body' => $body,
                    ]);
                    Logger::get()->debug("APM Request Fulfilled (Sync) - Status: " . $response->getStatusCode() . " - Body: " . (string)$response->getBody());
                } catch (\Throwable $e) {
                    Logger::get()->error("APM Request Rejected (Sync) - " . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            Logger::get()->error("Failure to queue async Segment - " . $e->getMessage());
        }
    }

    public static function flush()
    {
        if (empty(self::$_promises)) {
            return;
        }

        try {
            $results = \GuzzleHttp\Promise\Utils::settle(self::$_promises)->wait();
            foreach ($results as $result) {
                if (isset($result['state']) && $result['state'] === 'fulfilled') {
                    $response = $result['value'];
                    Logger::get()->debug("APM Request Fulfilled - Status: " . $response->getStatusCode() . " - Body: " . (string)$response->getBody());
                } else if (isset($result['state']) && $result['state'] === 'rejected') {
                    $reason = $result['reason'];
                    $msg = $reason instanceof \Throwable ? $reason->getMessage() : json_encode($reason);
                    Logger::get()->error("Async Promise Rejected - " . $msg);
                }
            }
        } catch (\Throwable $e) {
            Logger::get()->error("Failure to flush active segments - " . $e->getMessage());
        } finally {
            self::$_promises = [];
        }
    }


    public static function utf8_encode_rec($value)
    {
        if (!is_array($value) && ($value == "" || $value == null || (!$value && $value !== "0"))) {
            return " ";
        }

        $newarray = array();

        if (is_array($value)) {
            foreach ($value as $key => $data) {
                $newarray[self::utf8_validate($key)] = self::utf8_encode_rec($data);
            }
        } else {
            return self::utf8_validate($value);
        }

        return $newarray;
    }

    public static function utf8_validate($string, $reverse = 0)
    {
        if ($reverse == 0) {

            if (preg_match('!!u', $string)) {
                return $string;
            } else {
                return utf8_encode($string);
            }
        }

        // Decoding
        if ($reverse == 1) {

            if (preg_match('!!u', $string)) {
                return utf8_decode($string);
            } else {
                return $string;
            }
        }

        return false;
    }

    private function getBody(array $payload): string
    {
        $body = [
            "data" => JWT::encode(self::utf8_encode_rec($payload), $this->_hash, "HS256")
        ];

        return json_encode($body);
    }

    private function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->_token
        ];
    }
}
