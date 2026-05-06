<?php

namespace dbsnOOp\Integrations\Laravel;

use dbsnOOp\DSSegment;
use dbsnOOp\Integrations\Integration;

final class LaravelIntegration extends Integration
{
    public function integrate()
    {
        if (class_exists('Illuminate\Routing\Route')) {
            \dbsnOOp\add_trace_method(
                'Illuminate\Routing\Route',
                'run',
                [
                    'pre_exec' => function (&$segment, $args, $that) {
                        $segment->type = 'web';
                        $segment->name = 'Laravel Route: ' . $that->uri();
                        $segment->tags['laravel.uri'] = $that->uri();
                        $segment->tags['laravel.action'] = $that->getActionName();
                        $segment->tags['laravel.methods'] = implode(',', $that->methods());
                    }
                ]
            );
        }
    }
}
