<?php


namespace dbsnOOp\Integrations\Sohris;

use dbsnOOp\Integrations\Integration;
use Exception;

use const dbsnOOp\WEB_METHOD;
use const dbsnOOp\WEB_REMOTE_ADDR;
use const dbsnOOp\WEB_STATUS_CODE;
use const dbsnOOp\WEB_TARGET;

final class SohrisHttpIntegration extends Integration
{

    public function __construct()
    {
    }

    public function integrate()
    {
        \dbsnOOp\add_trace_method(
            "\Sohris\Http\Middleware\Logger",
            "__invoke",
            [
                "pos_exec" => function ($tracker, $args, $result) {
                    $tracker->object = "SohrisHttp::request";
                    $tracker->resource = "request";
                    $tracker->type = \dbsnOOp\TYPE_APP_WEB;

                    $tracker->info = [
                        WEB_TARGET => $args[0]->getRequestTarget(),
                        WEB_METHOD => $args[0]->getMethod(),
                        WEB_STATUS_CODE => $result->getStatusCode(),
                        WEB_REMOTE_ADDR => $args[0]->getServerParams()['REMOTE_ADDR']
                    ];
                }
            ]
        );
    }
}
