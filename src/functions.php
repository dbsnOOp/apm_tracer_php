<?php


namespace dbsnOOp;

use Exception;
use ReflectionMethod;
use Throwable;

/**
 * 
 */
function add_trace_function(string $function_name, array $opt)
{
    if (!function_exists($function_name)) {
        throw new Exception("Function $function_name() not found!");
    }
    runkit7_function_copy($function_name, __REDEFINED_NAME_FUNCTION__ . $function_name);
    runkit7_function_redefine($function_name, function (...$args) use ($function_name, $opt) {
        $tracker = Tracer::initTracker($function_name);
        Tracer::startTrack($tracker->_id());
        if (isset($opt['pre_exec']) && is_callable($opt['pre_exec']))
            call_user_func_array($opt['pre_exec'], [&$tracker, $args]);

        try {
            $result = call_user_func_array(__REDEFINED_NAME_FUNCTION__ . $function_name, $args);
        } catch (Exception $e) {
            Tracer::finishTrack($tracker->_id());
            throw $e;
        } catch (Throwable $e) {
            Tracer::finishTrack($tracker->_id());
            throw $e;
        }
        Tracer::stopTrack($tracker->_id());
        $info = [];
        if (isset($opt['pos_exec']) && is_callable($opt['pos_exec']))
            call_user_func_array($opt['pos_exec'], [&$tracker, $args, $result]);

        Tracer::finishTrack($tracker->_id(), $info);
        return $result;
    });
}

/**
 * 
 */
function add_trace_method(string $class_name, string $method_name, array $opt)
{

    if (!\class_exists($class_name)) {
        trigger_error("Class $class_name not found", E_USER_ERROR);
        return;
    }

    if (!\method_exists($class_name, $method_name)) {
        trigger_error("Method $class_name::$method_name() not found", E_USER_ERROR);
        return;
    }

    runkit7_method_copy($class_name, __REDEFINED_NAME_METHOD__ . $method_name, $class_name, $method_name);
    runkit7_method_redefine($class_name, $method_name, function (...$args) use ($class_name, $method_name, $opt) {
        $method = __REDEFINED_NAME_METHOD__ . $method_name;
        $method_reflection = new ReflectionMethod($class_name, $method);
        $obj = $method_reflection->isStatic() ?  NULL : $this;

        if (!Tracer::isValidTracer($obj)) {
            if (is_null($obj)) {
                return self::$method(...$args);
            } else {
                return $this->$method(...$args);
            }
        }
        $track = Tracer::initTracker();
        $track->object = "$class_name::$method_name";
        Tracer::startTrack($track->_id());
        if (isset($opt['pre_exec']) && is_callable($opt['pre_exec']))
            call_user_func_array($opt['pre_exec'], [&$track, $args]);
        try {
            if (is_null($obj)) {
                $result = self::$method(...$args);
            } else {
                $result = $this->$method(...$args);
            }
        } catch (Exception $e) {
            Tracer::finishTrack($track->_id(), []);
            throw $e;
        } catch (Throwable $e) {
            Tracer::finishTrack($track->_id(), []);
            throw $e;
        }
        Tracer::stopTrack($track->_id());
        if (isset($opt['pos_exec']) && is_callable($opt['pos_exec']))
            call_user_func_array($opt['pos_exec'], [&$track, $args, $result]);
        Tracer::finishTrack($track->_id());
        return $result;
    });
}
