<?php


namespace dbsnOOp\Integrations\Sohris;

use dbsnOOp\Integrations\Integration;
use dbsnOOp\Utils\Parameter;
use ReflectionClass;
use Sohris\Event\Utils as Utils;
use Sohris\Http\Router\Kernel;


final class SohrisEventsIntegration extends Integration
{

    public function __construct()
    {
    }

    public function integrate()
    {
        \dbsnOOp\add_trace_method(
            "Sohris\Event\Event\EventControl",
            "runEvent",
            [
                "pre_exec" => function ($segment, $args) {
                    list(, $component, $name) = $args;
                    if($name === 'firstRun') return;
                    $event = Utils::loadAnnotationsOfClass($component);
                    $ref = new ReflectionClass($component);
                    $segment->analyze = true;
                    $segment->component = $ref->getNamespaceName();
                    $segment->name = $ref->getName();
                    $annotations = $event['annotations'];
                    $segment->tags["event.start_running"] = false;
                    foreach ($annotations as $annotation) {
                        if (get_class($annotation) == "Sohris\Event\Annotations\Time") {
                            $segment->tags["event.type"] = $annotation->getType();
                            $segment->tags["event.interval"] = $annotation->getTime();
                        } else if (get_class($annotation) == "Sohris\Event\Annotations\StartRunning") {
                            $segment->tags["event.start_running"] = true;
                        }
                    }
                }
            ]
        );
    }
}
