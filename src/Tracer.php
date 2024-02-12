<?php


namespace dbsnOOp;

use dbsnOOp\Integrations\Integration;
use dbsnOOp\Integrations\Loader;
use dbsnOOp\Utils\Logger;
use dbsnOOp\Utils\Parameter;
use SplStack;

use const dbsnOOp\Type\APP_WEB;

class Tracer
{
    public static string $default_service = "dbsnoop-tracer";

    private static array $tracers = [];

    private static SplStack $currents_tracer;
    private static DSSegment $root_tracer;


    public static function init()
    {
        if (!self::defineEnvirounment()) return;
        self::$root_tracer = new DSSegment;
        self::$root_tracer->analyze = true;
        self::$root_tracer->service = self::$default_service;
        self::$root_tracer->component = "Script";

        self::$root_tracer->start();

        self::$currents_tracer = new SplStack;
        self::addSegment(self::$root_tracer);


        set_error_handler(function (...$err) {
            if (!(error_reporting() & $err[0])) {
                return false;
            }
            self::defineEnvirounment();
            $error =  [
                Parameter::ERR_NO => $err[0],
                Parameter::ERR_MSG => $err[1],
                Parameter::ERR_FILE => $err[2],
                Parameter::ERR_LINE => $err[3]
            ];

            switch ($err[0]) {
                case E_USER_ERROR:
                case E_ERROR:
                    $error[Parameter::ERR_TYPE] = Parameter::TRIGGER_ERROR;
                    break;
                case E_USER_WARNING:
                case E_WARNING:
                    $error[Parameter::ERR_TYPE] =Parameter::TRIGGER_WARNING;
                    break;
                case E_USER_NOTICE:
                case E_NOTICE:
                    $error[Parameter::ERR_TYPE] =Parameter::TRIGGER_NOTICE;
                    break;
                default:
                    return;
            }
            self::$currents_tracer->current()->error[] = $error;
        });

        register_shutdown_function(function () {
            self::defineEnvirounment();
            self::$root_tracer->stop();
            self::$root_tracer->finish();
        });

        Loader::init();
    }

    public static function defineEnvirounment(): bool
    {

        if (!getenv('DBSNOOP_APM_MODE')) {
            return false;
        } else {
            switch (getenv('DBSNOOP_APM_MODE')) {
                case "INTEGRAL":
                    Logger::$enable_level = Logger::ERROR;
                    break;
                case "INTEGRAL_DEBUG":
                    Logger::$enable_level = Logger::DEBUG;
                    break;
                case "TRACK_ONLY":
                    Logger::$enable_level = Logger::ERROR;
                    break;
                case "TRACK_ONLY_DEBUG":
                    Logger::$enable_level = Logger::DEBUG;
                    break;
                default:
                    trigger_error("The 'DBSNOOP_APM_MODE' (" . getenv('DBSNOOP_APM_MODE') . ") is not configured.", E_USER_WARNING);
            }
        }

        return true;
    }
    public static function install(string $name, $callback)
    {
        if (!isset(self::$tracers[$name]))
            self::$tracers[$name] = $callback;
    }

    public static function getSegment(string $name = ""): DSSegment
    {
        $segment = new DSSegment;
        $segment->name = "execution";
        $segment->service = self::$default_service;
        $segment->type = Parameter::APP_DEFAULT;
        $segment->component = $name;

        //Validar se existe algum trace para ser realizado sobre a estrutura do codigo
        if (isset(self::$tracers[$name]) && is_callable(self::$tracers[$name])) {
            self::$tracers[$name]($segment);
        } else {
            self::$currents_tracer->current()->addChild($segment);
        }

        self::addSegment($segment);
        return $segment;
    }

    public static function getCurrentSegment(): DSSegment
    {
        return self::$currents_tracer->current();
    }

    private static function addSegment(DSSegment $segment)
    {
        self::$currents_tracer->push($segment);
        self::$currents_tracer->rewind();
    }

    public static function removeSegment()
    {
        if (self::$currents_tracer->count() <= 1) return;      
        
        self::$currents_tracer->rewind();
    }
}
