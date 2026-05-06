<?php

namespace dbsnOOp\Integrations\PostgreSql;

use dbsnOOp\DSSegment;
use dbsnOOp\Integrations\Integration;
use dbsnOOp\Utils\Parameter;

final class PostgreSqlIntegration extends Integration
{
    public function integrate()
    {
        if (function_exists('pg_query')) {
            \dbsnOOp\add_trace_function(
                'pg_query',
                [
                    'pos_exec' => function (DSSegment $segment, $args, $result, $ex, $that) {
                        $query = isset($args[1]) ? $args[1] : (isset($args[0]) ? $args[0] : '');
                        $segment->type = Parameter::APP_DATABASE;
                        $segment->name = 'pg_query';
                        $segment->tags[Parameter::DB_TYPE] = 'postgresql';
                        $segment->tags[Parameter::DB_QUERY] = $query;
                        if ($result) {
                            $segment->tags[Parameter::QUERY_NUM_ROWS] = pg_num_rows($result);
                        }
                    }
                ]
            );
        }
    }
}
