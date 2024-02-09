<?php

namespace dbsnOOp\Utils;

final class Time
{

    public static function unixtime(int $precision)
    {
        return  (int) microtime(true) * $precision;
    }


    public static function performer()
    {
        return hrtime(true);
    }
}
