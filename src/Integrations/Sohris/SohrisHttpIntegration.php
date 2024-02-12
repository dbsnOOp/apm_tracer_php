<?php


namespace dbsnOOp\Integrations\Sohris;

use dbsnOOp\Integrations\Integration;
use dbsnOOp\Utils\Parameter;
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
                "pre_exec" => function ($segment, $args, $result) {
                    $segment->name = "SohrisHttp::request";
                    $segment->type = Parameter::APP_WEB;
                    
                },"pos_exec" => function ($segment, $args, $result) {
                    $segment->tags[Parameter::WEB_TARGET] = $args[0]->getRequestTarget();
                    $segment->tags[Parameter::WEB_METHOD] = $args[0]->getMethod();
                    $segment->tags[Parameter::WEB_STATUS_CODE] = $result->getStatusCode();
                    $segment->tags[Parameter::WEB_REMOTE_ADDR] = $args[0]->getServerParams()['REMOTE_ADDR'];
                    
                },
            ]
        );
    }
}
