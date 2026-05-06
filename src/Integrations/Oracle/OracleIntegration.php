<?php

namespace dbsnOOp\Integrations\Oracle;

use dbsnOOp\DSSegment;
use dbsnOOp\Integrations\Integration;
use dbsnOOp\Utils\Parameter;

final class OracleIntegration extends Integration
{
    public function integrate()
    {
        if (function_exists('oci_execute')) {
            \dbsnOOp\add_trace_function(
                'oci_execute',
                [
                    'pos_exec' => function (DSSegment $segment, $args, $result, $ex, $that) {
                        $segment->type = Parameter::APP_DATABASE;
                        $segment->name = 'oci_execute';
                        $segment->tags[Parameter::DB_TYPE] = 'oracle';
                    }
                ]
            );
        }
    }
}
