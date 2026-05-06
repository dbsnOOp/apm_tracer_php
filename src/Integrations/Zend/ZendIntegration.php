<?php

namespace dbsnOOp\Integrations\Zend;

use dbsnOOp\DSSegment;
use dbsnOOp\Integrations\Integration;

final class ZendIntegration extends Integration
{
    public function integrate()
    {
        // Novo Laminas Framework MVC
        if (class_exists('Laminas\Mvc\Application')) {
            \dbsnOOp\add_trace_method(
                'Laminas\Mvc\Application',
                'run',
                [
                    'pre_exec' => function (&$segment, $args, $that) {
                        $segment->type = 'web';
                        $segment->name = 'Laminas MVC Application';
                    }
                ]
            );
        }

        // Legado Zend Framework 1 Front Controller
        if (class_exists('Zend_Controller_Front')) {
            \dbsnOOp\add_trace_method(
                'Zend_Controller_Front',
                'dispatch',
                [
                    'pre_exec' => function (&$segment, $args, $that) {
                        $segment->type = 'web';
                        $segment->name = 'Zend Framework 1 Dispatch';
                    }
                ]
            );
        }
    }
}
