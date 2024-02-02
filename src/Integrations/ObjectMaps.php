<?php

namespace dbsnOOp\Integrations;

final class ObjectMaps
{
    private static $map;
    const PREFIX = "__dbsnoop__";


    public static function set($instance, $key, $value)
    {
        if (\class_exists("WeakMap", $autoload = false)) {
            if (!self::$map) {
                self::$map = new \WeekMap();
            }

            if (isset(self::$map[$instance])) {
                $store = &self::$map[$instance];
                $store[$key] = $value;
                return;
            }

            self::$map[$instance] = [$key => $value];
        } else {
            $scopedKey = self::PREFIX . $key;
            $instance->$scopedKey = $value;
        }
    }

    public static function get($instance, $key, $default = null)
    {

        if (\class_exists("WeakMap", $autoload = false)) {
            if (!self::$map || !isset(self::$map[$instance])) {
                return $default;
            }

            $store = self::$map[$instance];

            if (!isset($store[$key])) {
                return $default;
            }

            return $store[$key];
        } else {
            $scopedKey = self::PREFIX . $key;
            return property_exists($instance, $scopedKey) ? $instance->$scopedKey : $default;
        }
    }
}
