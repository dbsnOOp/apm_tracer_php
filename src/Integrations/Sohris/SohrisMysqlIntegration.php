<?php


namespace dbsnOOp\Integrations\Sohris;

use dbsnOOp\DSSegment;
use dbsnOOp\Integrations\Integration;
use dbsnOOp\Utils\Parameter;
use Exception;

final class SohrisMysqlIntegration extends Integration
{

    public function __construct()
    {
    }

    public function integrate()
    {
        \dbsnOOp\add_trace_method(
            "\Sohris\Mysql\Pool",
            "exec",
            [
                "pos_exec" => function (DSSegment $segment, $args, $result, $exception, $obj) {
                    $segment->name = "SohrisPool::exec";
                    $segment->type = Parameter::APP_DATABASE;
                    $segment->tags[Parameter::DB_QUERY] = $args[0];
                    $segment->tags[Parameter::QUERY_NUM_ROWS] = count($result->rows);
                }
            ]
        );
    }
}
