<?php

namespace dbsnOOp;

use Closure;
use dbsnOOp\Integrations\Loader;
use Exception;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise\Promise;
use SplStack;
use Throwable;

class Tracer
{
    private static string $_hash;
    private static int $_id;
    private static string $_uri;
    private static string $_token;
    private static Tracker $_tracker;
    private static int $_mode = 0;

    private static SplStack $trackers;
    private static Tracker $current_track;
    private static array $requests = [];

    public static function init()
    {
        if (!self::setEnvirounment()) return;
        self::$trackers = new SplStack;
        self::$_id = random_int(1000000000, 9999999999);
        self::$_tracker = new Tracker(self::$_id);
        self::$trackers->push(self::$_tracker);
        self::$trackers->rewind();
        self::$current_track = self::$_tracker;
        self::$_tracker->type = TYPE_APP_INIT_APP;
        self::$_tracker->resource = "dbsnOOp/Tracer";
        Loader::init();
        self::$_tracker->start();

        set_error_handler(function (...$err) {
            if (!(error_reporting() & $err[0])) {
                return false;
            }
            self::setEnvirounment();
            $tracker = new Tracker(self::$_id, self::$current_track);
            $tracker->start();
            switch ($err[0]) {
                case E_USER_ERROR:
                case E_ERROR:
                    $tracker->type = TYPE_TRIGGER_ERROR;
                    break;
                case E_USER_WARNING:
                case E_WARNING:
                    $tracker->type = TYPE_TRIGGER_WARNING;
                    break;
                case E_USER_NOTICE:
                case E_NOTICE:
                    $tracker->type = TYPE_TRIGGER_NOTICE;
                    break;
                default:
                    return;
            }
            $tracker->info = [
                "no" => $err[0],
                "str" => $err[1],
                "file" => $err[2],
                "line" => $err[3]
            ];
            $tracker->finish();
            self::sendRequest($tracker);
        });
        set_exception_handler(function (Throwable $e) {
            self::setEnvirounment();
            $tracker = new Tracker(self::$_id, self::$current_track);
            $tracker->start();
            $tracker->info = [
                "no" => $e->getCode(),
                "str" => $e->getMessage(),
                "file" => $e->getFile(),
                "line" => $e->getLine(),
                "trace" => $e->getTraceAsString()
            ];
            $tracker->finish();
            self::sendRequest($tracker);
        });
        register_shutdown_function(function () {
            self::setEnvirounment();
            self::$_tracker->type = TYPE_APP_FINISH_APP;
            self::$_tracker->finish();
            self::sendRequest(self::$_tracker);
            if (!empty(self::$requests)) {
                foreach (self::$requests as $request) {
                    if ($request instanceof Promise)
                        $request->wait();
                }
            }
        });
        self::sendRequest(self::$_tracker);
    }

    public static function setEnvirounment(): bool
    {

        if (!getenv('DBSNOOP_APM_MODE')) {
            return false;
        } else {
            switch (getenv('DBSNOOP_APM_MODE')) {
                case "INTEGRAL_MODE":
                    self::$_mode = INTEGRAL_MODE;
                    break;
                case "INTEGRAL_DEBUG_MODE":
                    self::$_mode = INTEGRAL_DEBUG_MODE;
                    break;
                case "TRACK_ONLY_MODE":
                    self::$_mode = TRACK_ONLY_MODE;
                    break;
                case "TRACK_ONLY_DEBUG_MODE":
                    self::$_mode = TRACK_ONLY_DEBUG_MODE;
                    break;
                default:
                    trigger_error("The 'DBSNOOP_APM_MODE' (" . getenv('DBSNOOP_APM_MODE') . ") is not configured.", E_USER_WARNING);
            }
        }

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

        self::$_hash = getenv('DBSNOOP_APM_APP_KEY');
        self::$_uri = getenv('DBSNOOP_APM_HOST_URL');
        self::$_token = getenv('DBSNOOP_APM_APP_TOKEN');
        return true;
    }

    public static function initTracker()
    {
        $track = new Tracker(self::$_id, self::$current_track);
        self::$current_track->addChild($track);
        self::$trackers->push($track);
        self::$trackers->rewind();
        self::$current_track = self::$trackers->current();
        return $track;
    }

    public static function getTracker()
    {
        return self::$current_track;
    }

    public static function startTrack()
    {
        self::$current_track->start();
    }

    public static function stopTrack()
    {
        self::$current_track->finish();
    }

    private static function stopChilds(Tracker $ref)
    {
        foreach ($ref->getChilds() as $child) {
            if (!empty($child->getChilds())) {
                self::stopChilds($child);
            }
            $child->finish();
        }
    }

    public static function finishTrack()
    {
        self::stopChilds(self::$current_track);
        self::$current_track->finish();
        self::sendRequest(self::$current_track);
        self::$trackers->pop();
        self::$trackers->rewind();
        self::$current_track = self::$trackers->current();
    }

    private static function sendRequest(Tracker $tracker)
    {
        try {
            if (self::$_mode === TRACK_ONLY_DEBUG_MODE || self::$_mode === INTEGRAL_DEBUG_MODE) {
                //var_dump(self::$current_track->getStats());
            }
            if (self::$_mode !== INTEGRAL_MODE && self::$_mode !== INTEGRAL_DEBUG_MODE) {
                return;
            }
            $body = [];
            $body = self::utf8_encode_rec($tracker->getStats());
            if (!empty($body))
                $body = ['data' => JWT::encode($body, self::$_hash, "HS256")];
            $opt = array(
                "headers" => array(
                    "Content-Type" => "application/json",
                    "Authorization" => "Bearer " . self::$_token,
                ),
                "body" => json_encode($body)
            );
            $client = new Client([
                "base_uri" => self::$_uri,
            ]);
            self::$requests[] = $client->request("POST", "/apm/send", $opt);
        } catch (ClientException $e) {
            return false;
        } catch (Exception $e) {
            return false;
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

    public static function isValidTracer($object): bool
    {
        if (!is_null($object) && $object instanceof Client) {
            return $object->getConfig("base_uri") !== self::$_uri;
        }
        return true;
    }
}
