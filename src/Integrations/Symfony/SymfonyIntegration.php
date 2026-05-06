<?php

namespace dbsnOOp\Integrations\Symfony;

use dbsnOOp\DSSegment;
use dbsnOOp\Integrations\Integration;

final class SymfonyIntegration extends Integration
{
    public function integrate()
    {
        if (class_exists('Symfony\Component\HttpKernel\HttpKernel')) {
            \dbsnOOp\add_trace_method(
                'Symfony\Component\HttpKernel\HttpKernel',
                'handle',
                [
                    'pre_exec' => function (&$segment, $args, $that) {
                        $request = $args[0] ?? null;
                        if ($request) {
                            $segment->type = 'web';
                            $segment->name = 'Symfony Route: ' . $request->getPathInfo();
                            $segment->tags['symfony.path'] = $request->getPathInfo();
                            $segment->tags['symfony.method'] = $request->getMethod();
                        }
                    }
                ]
            );
        }
    }
}
