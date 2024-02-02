<?php

namespace dbsnOOp;

final class Tracker
{

    public string $object = "";
    public string $resource = "";
    public array $tags = [];
    public string $type = TYPE_APP_DEFAULT;
    public array $info = [];

    public string $function = "";
    public string $class = "";

    private int $_id = 0;
    private int $_app_id = 0;
    private int $_parent_id = 0;

    private int $start = 0;
    private int $end = 0;
    private bool $finished = false;

    private array $init_stats = [];
    private array $end_stats = [];


    public function __construct(int $app_id, int $parent_id)
    {
        $this->_id = random_int(1000000000, 9999999999);
        $this->_parent_id = $parent_id;
        $this->_app_id = $app_id;
    }

    public function _id()
    {
        return $this->_id;
    }

    public function _parent_id()
    {
        return $this->_parent_id;
    }

    public function start()
    {
        if ($this->finished) return;
        $this->start = hrtime(true);
        $this->init_stats = getrusage();
    }

    public function finish()
    {
        $this->end = hrtime(true);
        $this->end_stats = getrusage();
        $this->finished = true;
    }

    public function getStats(): array
    {
        $func =  array_slice(debug_backtrace(1,0),4);
        $func[0]['function'] = $this->function;

        $status = [
            "_id" => $this->_id,
            "_app_id" => $this->_app_id,
            "_parent_id" => $this->_parent_id,
            "object" => $this->object,
            "resource" => $this->resource,
            "trace" => $func,
            "type" => $this->type,
            "tags" => $this->tags,
            "info" => $this->info,
            "start_time" => $this->start,
            "end_time" => $this->end,
            "execute_time" => $this->finished ? ($this->end - $this->start) : 0,
            "init_stats" => $this->init_stats,
            "finish_stats" => $this->end_stats,
            "execute_time" => $this->end - $this->start
        ];
        return $status;
    }
}
