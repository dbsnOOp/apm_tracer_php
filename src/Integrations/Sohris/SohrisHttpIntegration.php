<?php


namespace dbsnOOp\Integrations\Sohris;

use dbsnOOp\Integrations\Integration;
use dbsnOOp\Utils\Parameter;
use Exception;
use Sohris\Http\Router\Kernel;
use Sohris\Http\Utils;

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
                    $segment->analyze = true;
                    $request = $args[0];
                    $segment->type = Parameter::APP_WEB;
                    

                    
                    $route_hash = Utils::hashOfRoute($request->getUri()->getPath());
                    $route = Kernel::getClassOfRoute($route_hash);
                    $desc = explode("::",$route->callable);
                    $segment->component = $desc[0];
                    $segment->name = $desc[1];

                    if (isset($route->session_jwt))
                        $segment->tags["web.session"] = $route->session_jwt->needAuthorization();
                    if (isset($route->needed)) {
                        $segment->tags["web.body.expected"] = [];
                        foreach ($route->needed->getNeeded() as $needed) {
                            $segment->tags["web.body.expected"][] = $needed;
                        }
                    }
                }, "pos_exec" => function ($segment, $args, $result) {
                    $segment->tags[Parameter::WEB_TARGET] = $args[0]->getRequestTarget();
                    $segment->tags[Parameter::WEB_METHOD] = $args[0]->getMethod();
                    $segment->tags[Parameter::WEB_STATUS_CODE] = $result->getStatusCode();
                    $segment->tags[Parameter::WEB_REMOTE_ADDR] = $args[0]->getServerParams()['REMOTE_ADDR'];
                },
            ]
        );
    }
}
