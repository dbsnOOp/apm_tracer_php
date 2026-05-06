<?php

namespace dbsnOOp\Integrations\Memcached;

use dbsnOOp\DSSegment;
use dbsnOOp\Integrations\Integration;

final class MemcachedIntegration extends Integration
{
    public function integrate()
    {
        if (class_exists('Memcached')) {
            \dbsnOOp\add_trace_method(
                'Memcached',
                'get',
                [
                    'pos_exec' => function (DSSegment $segment, $args, $result, $ex, $that) {
                        $segment->type = 'cache';
                        $segment->name = 'Memcached::get';
                        $segment->tags['cache.key'] = $args[0] ?? '';
                        $segment->tags['cache.status'] = is_null($result) || $result === false ? 'miss' : 'hit';
                    }
                ]
            );

            \dbsnOOp\add_trace_method(
                'Memcached',
                'set',
                [
                    'pos_exec' => function (DSSegment $segment, $args, $result, $ex, $that) {
                        $segment->type = 'cache';
                        $segment->name = 'Memcached::set';
                        $segment->tags['cache.key'] = $args[0] ?? '';
                    }
                ]
            );
        }
    }
}
