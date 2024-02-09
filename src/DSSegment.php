<?php

namespace dbsnOOp;

use dbsnOOp\Utils\Parameter;
use dbsnOOp\Utils\Request;
use dbsnOOp\Utils\Time;

final class DSSegment
{

    /**
     * @var string The name of service to be assigned in segment
     */
    public string $service = "";

    /**
     * 
     * @var string The name of trace
     */
    public string $name = "";

    /**
     * @var string A specific realm within a application
     */
    public string $component = "";

    /**
     * @var string The type of trace 
     */
    public string $type = "";

    /**
     * @var array The meta data of application 
     */
    public array $meta = [];

    /**
     * @var array Tags assigned to the segment
     */
    public array $tags = [];

    private array $init_metrics = [];

    public bool $analyze = false;

    /**
     * @var DSSegments[] The childs segments  
     */
    private array $segment_tracer = [];

    public array $error = [];

    public function __construct()
    {
        $this->meta = [];
        $this->tags = [];
    }

    public function addSegment(DSSegment $segment)
    {
        $this->segment_tracer[] = $segment;
    }

    public function start()
    {
        $this->meta[Parameter::SEGMENT_START] = Time::unixtime(1000);
        $this->meta[Parameter::SEGMENT_START_NS] = Time::performer();
        $this->init_metrics = $this->getMetrics();
    }

    public function stop()
    {
        if (!isset($this->meta[Parameter::SEGMENT_START])) return;
        $this->meta[Parameter::SEGMENT_FINISH] = Time::unixtime(1000);
        $this->meta[Parameter::SEGMENT_FINISH_NS] = Time::performer();
        $this->meta[Parameter::SEGMENT_DURATION] = $this->meta[Parameter::SEGMENT_FINISH_NS] - $this->meta[Parameter::SEGMENT_START_NS];

        foreach ($this->getMetrics() as $key => $metric) {
            $this->meta[$key] = $metric > 0 ? $metric - $this->init_metrics[$key] : 0;
        }
    }

    public function isOpen()
    {
        return $this->meta[Parameter::SEGMENT_DURATION] <= 0;
    }

    public function finish()
    {
        foreach ($this->segment_tracer as &$segment) {
            if ($segment->isOpen())
                $segment->finish();
        }
        if ($this->analyze) {
            $request = new Request;
            $request->send($this);
        }
    }

    private function getMetrics()
    {
        $metrics = getrusage();

        return [
            Parameter::SEGMENT_METRIC_UTIME => $metrics['ru_utime.tv_usec'],
            Parameter::SEGMENT_METRIC_STIME => $metrics['ru_stime.tv_usec'],
            Parameter::SEGMENT_METRIC_IN_BLOCK => $metrics['ru_inblock'],
            Parameter::SEGMENT_METRIC_OUT_BLOCK => $metrics['ru_oublock'],
            Parameter::SEGMENT_METRIC_MSG_RECV => $metrics['ru_msgrcv'],
            Parameter::SEGMENT_METRIC_MSG_SEND => $metrics['ru_msgsnd'],
            Parameter::SEGMENT_METRIC_IX_RSS => $metrics['ru_ixrss'],
            Parameter::SEGMENT_METRIC_ID_RSS => $metrics['ru_idrss'],
            Parameter::SEGMENT_METRIC_IS_RSS => $metrics['ru_isrss']
        ];
    }

    public function setError(...$err)
    {
        $this->error = $err;
    }

    public function addChild(DSSegment $segment)
    {
        $this->segment_tracer[] = $segment;
    }

    public function getStructure()
    {
        return [
            "service" => $this->service,
            "name" => $this->name,
            "component" => $this->component,
            "type" => $this->type,
            "meta" => $this->meta,
            "tags" => $this->tags,
            "segments" => array_map(fn ($el) => $el->getStructure(), $this->segment_tracer),
            "error" => $this->error
        ];
    }
}
