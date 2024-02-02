<?php


namespace dbsnOOp;

use Exception;
use GuzzleHttp\Promise\Promise;
use ReflectionMethod;
use Throwable;


function resolve_promise($type, $result, &$track, $opt, $args, &$that = null)
{
    if ($type == 'guzzle') {
        $r = new Promise();
        $r->then(function ($results) use ($result, $opt, &$track, $args, &$that) {

            if (!is_null($track)) {
                Tracer::stopTrack($track->_id());
            }
            if (isset($opt['pos_exec']) && is_callable($opt['pos_exec']))
                call_user_func_array($opt['pos_exec'], [&$track,$args, $results, $that]);

            if (!is_null($track)) {
                Tracer::finishTrack($track->_id());
            }
            $result->resolve($results);
            return $result;
        }, function ($e) use ($result, &$track) {
            Tracer::finishTrack($track->_id());
            $result->reject($e);
            return $result;
        });
        return $r;
    } else if ($type == 'react') {
        return $result->then(function ($results) use ($opt, &$track, $args,&$that) {

            if (!is_null($track)) {
                Tracer::stopTrack($track->_id());
            }
            if (isset($opt['pos_exec']) && is_callable($opt['pos_exec']))
                call_user_func_array($opt['pos_exec'], [&$track, $args, $results, $that]);
            if (!is_null($track)) {
                Tracer::finishTrack($track->_id());
            }
            return $results;
        }, function ($e) use (&$track) {
            if (!is_null($track)) {
                Tracer::finishTrack($track->_id());
            }
            return $e;
        });
    }
    return $result;
}

function resolve_default($result, &$track, $opt, $args,&$that = null)
{
    if (!is_null($track)) {
        Tracer::stopTrack($track->_id());
    }
    $info = [];
    if (isset($opt['pos_exec']) && is_callable($opt['pos_exec']))
        call_user_func_array($opt['pos_exec'], [&$track, $args, $result, &$that]);

    if (!is_null($track)) {
        Tracer::finishTrack($track->_id(), $info);
    }
    return $result;
}

function resolve_result($result, &$tracker, $opt, $args, &$that = null)
{
    if (is_object($result)) {
        $interfaces = class_implements($result);
        if (isset($interfaces['GuzzleHttp\Promise\PromiseInterface'])) {
            return resolve_promise('guzzle', $result, $tracker, $opt, $args, $that);
        } else if (isset($interfaces['React\Promise\PromiseInterface'])) {
            return resolve_promise('react', $result, $tracker, $opt, $args, $that);
        }
    }
    return resolve_default($result, $tracker, $opt, $args, $that);
}

/**
 * 
 */
function add_trace_function(string $function_name, array $opt)
{
    if (!function_exists($function_name)) {
        return;
        throw new Exception("Function $function_name() not found!");
    }
    \uopz_set_return($function_name, function (...$args) use ($function_name, $opt) {
        $tracker = Tracer::initTracker();
        $tracker->object = $function_name;
        if (isset($opt['pre_exec']) && is_callable($opt['pre_exec']))
            call_user_func_array($opt['pre_exec'], [&$tracker, $args]);
        Tracer::startTrack($tracker->_id());
        try {
            $result = $function_name(...$args);
        } catch (Exception $e) {
            Tracer::finishTrack($tracker->_id());
            throw $e;
        } catch (Throwable $e) {
            Tracer::finishTrack($tracker->_id());
            throw $e;
        }
        return resolve_result($result, $tracker, $opt, $args);
    }, true);
}

/**
 * 
 */
function add_trace_method(string $class_name, string $method_name, array $opt)
{

    if (!\class_exists($class_name)) {
        return;
        trigger_error("Class $class_name not found", E_USER_ERROR);
        return;
    }

    if (!\method_exists($class_name, $method_name)) {
        trigger_error("Method $class_name::$method_name() not found", E_USER_ERROR);
        return;
    }
    \uopz_set_return($class_name, $method_name, function (...$args) use ($class_name, $method_name, $opt) {
        $method_reflection = new ReflectionMethod($class_name, $method_name);
        $obj = $method_reflection->isStatic() ?  NULL : $this;
        $track = Tracer::initTracker();
        if (isset($opt['pre_exec']) && is_callable($opt['pre_exec']))
            call_user_func_array($opt['pre_exec'], [&$track, $args, $obj]);
        Tracer::startTrack($track->_id());
        try {
            if (Tracer::isValidTracer($obj)) {
                if (is_null($obj)) {
                    $result = self::$method_name(...$args);
                } else {
                    $result = $this->$method_name(...$args);
                }
            }
        } catch (Exception $e) {
            Tracer::finishTrack($track->_id(), []);
            throw $e;
        } catch (Throwable $e) {
            Tracer::finishTrack($track->_id(), []);
            throw $e;
        }
        
        return resolve_result($result, $track, $opt, $args, $obj);
    },true);
}

/**
 * 
 */
function add_hook_function(string $function_name, array $opt)
{
    if (!function_exists($function_name)) {
        //throw new Exception("Function $function_name() not found!");
        return;
    }
    \uopz_set_return($function_name, function (...$args) use ($function_name, $opt) { 
        if (isset($opt['pre_exec']) && is_callable($opt['pre_exec']))
            call_user_func_array($opt['pre_exec'], [$args]);
        $result = $function_name(...$args);
        return resolve_result($result, $tracker, $opt, $args);
    },true);
}

/**
 * 
 */
function add_hook_method(string $class_name, string $method_name, array $opt)
{

    if (!\class_exists($class_name)) {
        return ;
        trigger_error("Class $class_name not found", E_USER_ERROR);
        return;
    }

    if (!\method_exists($class_name, $method_name)) {
        trigger_error("Method $class_name::$method_name() not found", E_USER_ERROR);
        return;
    }
    \uopz_set_return($class_name, $method_name, function (...$args) use ($class_name, $method_name, $opt) {
        $method_reflection = new ReflectionMethod($class_name, $method_name);
        $obj = $method_reflection->isStatic() ?  NULL : $this;
        if (isset($opt['pre_exec']) && is_callable($opt['pre_exec']))
            call_user_func_array($opt['pre_exec'], [$args]);
        if (Tracer::isValidTracer($obj)) {
            if (is_null($obj)) {
                $result = self::$method_name(...$args);
            } else {
                $result = $this->$method_name(...$args);
            }
        }
        return resolve_result($result, $track, $opt, $args, $obj);
    },true);
}
