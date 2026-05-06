<?php

namespace dbsnOOp\Integrations\Mssql;

use dbsnOOp\DSSegment;
use dbsnOOp\Integrations\Integration;
use dbsnOOp\Utils\Parameter;

final class MssqlIntegration extends Integration
{
    public function integrate()
    {
        if (function_exists('sqlsrv_query')) {
            \dbsnOOp\add_trace_function(
                'sqlsrv_query',
                [
                    'pos_exec' => function (DSSegment $segment, $args, $result, $ex, $that) {
                        $query = $args[1] ?? '';
                        $segment->type = Parameter::APP_DATABASE;
                        $segment->name = 'sqlsrv_query';
                        $segment->tags[Parameter::DB_TYPE] = 'mssql';
                        $segment->tags[Parameter::DB_QUERY] = $query;
                        if ($result) {
                            $segment->tags[Parameter::QUERY_NUM_ROWS] = sqlsrv_num_rows($result) ?: 0;
                        }
                    }
                ]
            );
        }
    }
}
