<?php

namespace dbsnOOp\Integrations\Redis;

use dbsnOOp\DSSegment;
use dbsnOOp\Integrations\Integration;
use dbsnOOp\Utils\Parameter;

final class RedisIntegration extends Integration
{
    public function integrate()
    {
        // Phpredis (ext-redis) OO Mode
        if (class_exists('Redis')) {
            \dbsnOOp\add_trace_method(
                'Redis',
                'get',
                [
                    'pos_exec' => function (DSSegment $segment, $args, $result, $ex, $that) {
                        $segment->type = 'cache';
                        $segment->name = 'Redis::get';
                        $segment->tags['cache.key'] = $args[0] ?? '';
                        $segment->tags['cache.status'] = is_null($result) || $result === false ? 'miss' : 'hit';
                    }
                ]
            );

            \dbsnOOp\add_trace_method(
                'Redis',
                'set',
                [
                    'pos_exec' => function (DSSegment $segment, $args, $result, $ex, $that) {
                        $segment->type = 'cache';
                        $segment->name = 'Redis::set';
                        $segment->tags['cache.key'] = $args[0] ?? '';
                    }
                ]
            );
        }

        // Predis (Composer Package)
        if (class_exists('Predis\Client')) {
            \dbsnOOp\add_trace_method(
                'Predis\Client',
                '__call',
                [
                    'pos_exec' => function (DSSegment $segment, $args, $result, $ex, $that) {
                        $method = $args[0] ?? 'command';
                        $segment->type = 'cache';
                        $segment->name = "Predis::" . $method;
                        $segment->tags['cache.key'] = isset($args[1][0]) ? $args[1][0] : '';
                    }
                ]
            );
        }
    }
}
