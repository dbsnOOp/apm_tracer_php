<?php


namespace dbsnOOp\Integrations\Sohris;

use dbsnOOp\Integrations\Integration;

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
                "pos_exec" => function ($tracker, $args) {
                    $tracker->object = "SohrisHttp";
                    $tracker->type = TYPE_APP_WEB;
                    if ($args[0] instanceof  \Psr\Http\Message\ResponseInterface) {
                        $tracker->info['web_uri'] = $args[0]->getRequestTarget();
                        $tracker->info['web_response_code'] = $args[0]->getStatusCode();
                        $tracker->info['web_method'] = $args[0]->getMethod();
                    }
                }
            ]
        );
    }
}
