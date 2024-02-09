<?php


namespace dbsnOOp;

use Exception;
use GuzzleHttp\Promise\Promise;
use ReflectionMethod;
use Throwable;

function resolve_default($result, &$track, $opt, $args, $exception = null, &$that = null, $do_close = true)
{
    if ($do_close && $track !== null)
        $track->stop();

    if (isset($opt['pos_exec']) && is_callable($opt['pos_exec']))
        call_user_func_array($opt['pos_exec'], [&$track, $args, $result, $exception, &$that]);

    if ($do_close && $track !== null)
        $track->finish();

    Tracer::removeSegment();
    return $result;
}

function resolve_promise($type, $result, &$track, $opt, $args, &$that = null, $do_close)
{
    if ($type == 'guzzle') {
        $r = new Promise();
        $r->then(function ($results) use ($result, $opt, &$track, $args, &$that, $do_close) {
            resolve_default($results, $track, $opt, $args, null, $that, $do_close);
            $result->resolve($results);
            return $result;
        }, function ($e) use ($result, $opt, &$track, $args, &$that, $do_close) {
            resolve_default(null, $track, $opt, $args, $e, $that, $do_close);
            $result->reject($e);
            return $result;
        });
        return $r;
    } else if ($type == 'react') {
        return $result->then(function ($results) use ($opt, &$track, $args, &$that, $do_close) {
            resolve_default($results, $track, $opt, $args, null, $that, $do_close);
            return $results;
        }, function ($e) use ($opt, &$track, $args, &$that, $do_close) {
            resolve_default(null, $track, $opt, $args, $e, $that, $do_close);
            return $e;
        });
    }
    return $result;
}

function resolve($result, &$tracker, $opt, $args, $exception = null, &$that = null, $do_close = true)
{
    if (is_object($result)) {
        $interfaces = class_implements($result);
        if (isset($interfaces['GuzzleHttp\Promise\PromiseInterface'])) {
            return resolve_promise('guzzle', $result, $tracker, $opt, $args, $exception, $that, $do_close);
        } else if (isset($interfaces['React\Promise\PromiseInterface'])) {
            return resolve_promise('react', $result, $tracker, $opt, $args, $exception, $that, $do_close);
        }
    }
    return resolve_default($result, $tracker, $opt, $args,$exception, $that, $do_close);
}

/**
 * 
 */
function add_trace_function(string $function_name, array $opt = [])
{
    if (!function_exists($function_name)) {
        return;
        throw new Exception("Function $function_name() not found!");
    }
    \uopz_set_return($function_name, function (...$args) use ($function_name, $opt) {
        $segment = Tracer::getSegment($function_name);
        $exception = null;
        if (isset($opt['pre_exec']) && is_callable($opt['pre_exec'])) {
            call_user_func_array($opt['pre_exec'], [$segment, $args]);
        }
        $segment->start();
        try {
            $result = $function_name($args);
        } catch (Throwable $e) {
            $exception = $e;
        }
        return resolve($result, $segment, $opt, $args, $exception);
    }, true);
}

/**
 * 
 */
function add_trace_method(string $class_name, string $method_name, array $opt = [])
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
        $name = "$class_name::$method_name";
        $method_reflection = new ReflectionMethod($class_name, $method_name);
        $obj = $method_reflection->isStatic() ?  NULL : $this;
        $segment = Tracer::getSegment($name);

        if (isset($opt['pre_exec']) && is_callable($opt['pre_exec']))
            call_user_func_array($opt['pre_exec'], [&$segment, $args, $obj]);
        $exception = null;
        $segment->start();
        try {
            if (is_null($obj)) {
                $result = self::$method_name(...$args);
            } else {
                $result = $this->$method_name(...$args);
            }
        } catch (Throwable $e) {
            $exception = $e;
        }

        return resolve($result, $segment, $opt, $args, $exception, $obj);
    }, true);
}

/**
 * 
 */
function add_hook_function(string $function_name, array $opt)
{
    if (!function_exists($function_name)) {
        return;
        throw new Exception("Function $function_name() not found!");
    }
    \uopz_set_return($function_name, function (...$args) use ($function_name, $opt) {
        $exception = null;
        $track = Tracer::getCurrentSegment();
        if (isset($opt['pre_exec']) && is_callable($opt['pre_exec'])) {
            call_user_func_array($opt['pre_exec'], [$args]);
        }
        try {
            $result = $function_name($args);
        } catch (Throwable $e) {
            $exception = $e;
        }
        return resolve($result, $track, $opt, $args, $exception, null, false);
    }, true);
}

/**
 * 
 */
function add_hook_method(string $class_name, string $method_name, array $opt)
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
        if (isset($opt['pre_exec']) && is_callable($opt['pre_exec']))
            call_user_func_array($opt['pre_exec'], [null, $args, $obj]);
        $exception = null;
        $track = Tracer::getCurrentSegment();
        try {
            if (is_null($obj)) {
                $result = self::$method_name(...$args);
            } else {
                $result = $this->$method_name(...$args);
            }
        } catch (Throwable $e) {
            $exception = $e;
        }

        return resolve($result, $track, $opt, $args, $exception, $obj, false);
    }, true);
}

function install_method_tracer(string $class, string $method, callable $callback)
{
    $name = "$class::$method";
    Tracer::install($name, $callback);
}

function install_function_tracer(string $function, callable $callback)
{
    Tracer::install($function, $callback);
}
