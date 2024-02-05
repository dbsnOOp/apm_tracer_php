<?php

namespace dbsnOOp;

use SplStack;

final class Tracker
{

    public string $object = "";
    public string $resource = "";
    public array $tags = [];
    public string $type = TYPE_APP_DEFAULT;
    public array $info = [];
    public string $clear_path = "";

    public string $function = "";
    public string $class = "";

    private int $_id = 0;
    private int $_app_id = 0;
    private Tracker $_parent;

    private int $start = 0;
    private int $end = 0;
    private int $ns_start = 0;
    private int $ns_end = 0;
    private bool $finished = false;

    private array $init_stats = [];
    private array $end_stats = [];
    private array $childs = [];
    private Tracker $current_child;


    public function __construct(int $app_id, $parent = null)
    {
        $this->_id = random_int(1000000000, 9999999999);

        if (isset($parent)) {
            $this->_parent = $parent;
        } else {
            $this->_parent = $this;
        }
        $this->_app_id = $app_id;
        $this->clear_path = dirname(__FILE__);
    }

    public function _id()
    {
        return $this->_id;
    }

    public function _parent(): Tracker
    {
        return $this->_parent;
    }

    public function _app_id()
    {
        return $this->_app_id;
    }

    public function start()
    {
        if ($this->finished) return;
        $this->ns_start = hrtime(true);
        $this->start = time();
        $this->init_stats = getrusage();
    }



    public function finish()
    {
        if($this->finished) return ;
        $this->ns_end = hrtime(true);
        $this->end = time();
        $this->end_stats = getrusage();
        $this->finished = true;
    }

    private function getStatistics(): array
    {

        $st = [];

        foreach ($this->init_stats as $key => $val) {
            if (isset($this->end_stats[$key])) {
                $st[$key] = $this->end_stats[$key] - $val;
            } else {
                $st[$key] = 0;
            }
        }
        return $st;
    }

    public function __toString()
    {
        return json_encode($this->getStats());
    }

    public function __getInfo()
    {
        $trace = [];
        $backtrack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        foreach ($backtrack as $key => $btrack) {
            if (
                $btrack['file'] == $this->clear_path . DIRECTORY_SEPARATOR . "functions.php" &&
                $btrack['function'] == "dbsnOOp\\resolve_result"
            ) {
                $trace = array_slice($backtrack, $key + 1);
                break;
            }
        }
        if (!empty($trace)) {
            $trace[0]['function'] = $this->function;
            $status['meta']["_file"] = $trace[0]['file'];
            $status['meta']["_line"] = $trace[0]['line'];
            $status['meta']["_function"] = $trace[0]['function'];
            $status['meta']["_class"] = $trace[0]['class'];
            $status['meta']["_script_file"] = $trace[count($trace) - 1]['file'];
        }
        return [
            "type" => $this->type,
            "tags" => $this->tags,
            "meta" => [
                "_id" => $this->_id,
                "_app_id" => $this->_app_id,
                "_parent_id" => $this->_parent->_id(),
                "_childs" => array_map(function($el) {
                    return $el->__getInfo();
                }, $this->childs),
            ],
            "times" => [
                "start" => $this->start,
                "end" => $this->end,
                "duration" => $this->finished ? $this->end - $this->start : 0,
                "duration_ns" => $this->finished ? $this->ns_end - $this->ns_start : 0
            ],
            "object" => $this->object,
            "resource" => $this->resource,
            "info" => $this->info,
            "metrics" => $this->getStatistics()
        ];
    }

    public function getStats(): array
    {
        $trace = [];
        $backtrack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        foreach ($backtrack as $key => $btrack) {
            if (
                $btrack['file'] == $this->clear_path . DIRECTORY_SEPARATOR . "functions.php" &&
                $btrack['function'] == "dbsnOOp\\resolve_result"
            ) {
                $trace = array_slice($backtrack, $key + 1);
                break;
            }
        }


        $status = [
            "type" => $this->type,
            "tags" => $this->tags,
            "meta" => [
                "_id" => $this->_id,
                "_app_id" => $this->_app_id,
                "_parent_id" => $this->_parent->_id(),
                "_childs" => array_map(function($el) {
                    return $el->__getInfo();
                },$this->childs)
            ],
            "times" => [
                "start" => $this->start,
                "end" => $this->end,
                "duration" => $this->finished ? $this->end - $this->start : 0,
                "duration_ns" => $this->finished ? $this->ns_end - $this->ns_start : 0
            ],
            "object" => $this->object,
            "resource" => $this->resource,
            "trace" => $trace,
            "info" => $this->info,
            "metrics" => $this->getStatistics()
        ];

        if (!empty($trace)) {
            $trace[0]['function'] = $this->function;
            $status['meta']["_file"] = $trace[0]['file'];
            $status['meta']["_line"] = $trace[0]['line'];
            $status['meta']["_function"] = $trace[0]['function'];
            $status['meta']["_class"] = $trace[0]['class'];
            $status['meta']["_script_file"] = $trace[count($trace) - 1]['file'];
        }
        return $status;
    }

    public function addChild(Tracker $child): void
    {
        $this->childs[] = $child;
        $this->current_child = $child;
    }

    public function getChilds(): array
    {
        return $this->childs;
    }
}
