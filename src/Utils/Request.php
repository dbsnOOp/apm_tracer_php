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

        $ch = \curl_init();

        \curl_setopt($ch, CURLOPT_URL, 'https://' . $this->_uri . "/v2/apm/send");
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, CURLOPT_POST, true);
        \curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getBody($payload));
        \curl_setopt($ch, CURLOPT_TIMEOUT_MS, self::DEFAULT_TIMEOUT);
        \curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, self::DEFAULT_CONNECTION_TIMEOUT);
        \curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        \curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders());
        \curl_setopt($ch, CURLOPT_VERBOSE, false);


        if (($response = \curl_exec($ch)) === false) {
            $errno = \curl_errno($ch);
            $errmsg = \curl_error($ch);
            Logger::get()->error("Failue to send Segment - curl - [$errno]$errmsg");
            return;
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($status === 415) {
            Logger::get()->error('Failue to send Segment, the package need upgrade!');
            return;
        }

        if ($status !== 200) {
            Logger::get()->error("Failue to send Segment - request - [$status]$response");
            return;
        }

        Logger::get()->debug("Succefully Send");
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
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->_token
        ];
    }
}
