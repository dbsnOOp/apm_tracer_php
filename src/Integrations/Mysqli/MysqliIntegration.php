<?php

namespace dbsnOOp\Integrations\Mysqli;

use dbsnOOp\DSSegment;
use dbsnOOp\Integrations\Integration;
use dbsnOOp\Integrations\ObjectMaps;
use dbsnOOp\Utils\Parameter;

use const dbsnOOp\DB_NAME;
use const dbsnOOp\DB_QUERY;
use const dbsnOOp\QUERY_NUM_ROWS;
use const dbsnOOp\TYPE_APP_DATABASE_QUERY;

final class MysqliIntegration extends Integration
{

    const DATABASE_CONFIG_KEY = "database_config_key";

    public function __construct()
    {
    }

    public function integrate()
    {
        $this->defineConnections();
        $this->defineSQLStmt();
    }

    private function defineConnections()
    {

        //Definec Structure o SQL DB
        //1- 
        \dbsnOOp\add_hook_function(
            'mysqli_connect',
            [
                "pos_exec" => function ($segment, $args, $result) {
                    if ($result !== false) {
                        $info = $this->getMysqliInfo($args);
                        ObjectMaps::set($this, self::DATABASE_CONFIG_KEY, $info);
                    }
                }
            ]
        );
        //2-
        // \dbsnOOp\add_hook_method(
        //     'mysqli',
        //     "__construct",
        //     [
        //         "pos_exec" => function ($args, $result) {
        //             $info = $this->getMysqliInfo($args);
        //             ObjectMaps::set($result, self::DATABASE_CONFIG_KEY, $info);
        //         }
        //     ]
        // );
        //3-
        \dbsnOOp\add_hook_function(
            'mysqli_real_connect',
            [
                "pos_exec" => function ($segment, $args, $result) {
                    list($mysqli) = $args;
                    $host = empty($args[1]) ? null : $args[0];
                    $dbName = empty($args[4]) ? null : $args[4];
                    if ($result !== false) {
                        $info = $this->getMysqliInfo([$host, "", "", $dbName]);
                        ObjectMaps::set($this, self::DATABASE_CONFIG_KEY, $info);
                    }
                }
            ]
        );
        //4-
        \dbsnOOp\add_hook_method(
            'mysqli',
            'real_connect',
            [
                "pos_exec" => function ($segment, $args, $result) {
                    $info = $this->getMysqliInfo($args);
                    ObjectMaps::set($this, self::DATABASE_CONFIG_KEY, $info);
                }
            ]
        );

        //Detect database change
        //1-
        \dbsnOOp\add_hook_function(
            "mysqli_select_db",
            [
                "pos_exec" => function ($segment, $args, $result) {
                    list($mysqli, $dbName) = $args;
                    $object = ObjectMaps::get($this, self::DATABASE_CONFIG_KEY, []);
                    $object[Parameter::DB_NAME] = $dbName;
                    ObjectMaps::set($this, self::DATABASE_CONFIG_KEY, $object);
                }
            ]
        );

        //2-
        \dbsnOOp\add_hook_method(
            'mysqli',
            'select_db',
            [
                "pos_exec" => function ($segment, $args, $result) {
                    list($dbName) = $args;
                    $object = ObjectMaps::get($this, self::DATABASE_CONFIG_KEY, []);
                    $object[Parameter::DB_NAME] = $dbName;
                    ObjectMaps::set($this, self::DATABASE_CONFIG_KEY, $object);
                }
            ]
        );
    }

    private function defineSQLStmt()
    {
        //Procedural Mode
        //1-
        \dbsnOOp\add_trace_function(
            "mysqli_query",
            [
                "pos_exec" => function (DSSegment $segment, $args, $result, $ex, $that) {
                    list($mysqli, $query) = $args;
                    $segment->name = "mysqli_query";
                    $segment->type = Parameter::APP_DATABASE;
                    $info = ObjectMaps::get($this, self::DATABASE_CONFIG_KEY, []);
                    $info[Parameter::DB_QUERY] = $query;
                    $info[Parameter::QUERY_NUM_ROWS] = mysqli_num_rows($result);
                    $segment->tags = array_merge($segment->tags, $info);
                }
            ]
        );
        //2-
        \dbsnOOp\add_hook_function(
            "mysqli_prepare",
            [
                "pos_exec" => function ($segment, $args, $result) {
                    list($mysqli, $query) = $args;
                    $info = ObjectMaps::get($this, self::DATABASE_CONFIG_KEY, []);
                    $info[Parameter::DB_QUERY] = $query;
                    ObjectMaps::set($this, self::DATABASE_CONFIG_KEY, $info);
                }
            ]
        );
        //3-
        \dbsnOOp\add_trace_function(
            "mysqli_stmt_execute",
            [
                "pos_exec" => function (DSSegment $segment, $args, $result, $ex, $that) {
                    list($mysqli) = $args;
                    $segment->name = "mysqli_stmt_execute";
                    $segment->type = Parameter::APP_DATABASE;
                    $info = ObjectMaps::get($this, self::DATABASE_CONFIG_KEY, []);
                    $info[Parameter::QUERY_NUM_ROWS] = mysqli_stmt_affected_rows($result);
                    $segment->tags = array_merge($segment->tags, $info);
                }
            ]
        );
        //OO Mode
        //1-
        \dbsnOOp\add_trace_method(
            "mysqli",
            "query",
            [
                "pos_exec" => function (DSSegment $segment, $args, $result, $ex, $that) {
                    list($query) = $args;
                    $segment->name = "mysqli::query";
                    $segment->type = Parameter::APP_DATABASE;
                    $info = ObjectMaps::get($this, self::DATABASE_CONFIG_KEY, []);
                    $info[Parameter::DB_QUERY] = $query;
                    $info[Parameter::QUERY_NUM_ROWS] = $result->affected_rows;
                    $segment->tags = array_merge($segment->tags, $info);
                }
            ]
        );
        //2-
        \dbsnOOp\add_hook_method(
            "mysqli",
            "prepare",
            [
                "pos_exec" => function (DSSegment $segment, $args, $result, $ex, $that) {
                    list($query) = $args;
                    if($result !== false)
                    {
                        $info = ObjectMaps::get($this, self::DATABASE_CONFIG_KEY, []);
                        $info[Parameter::DB_QUERY] = $query;
                        $info["host_info"] = $that->host_info;
                        ObjectMaps::set($this, self::DATABASE_CONFIG_KEY, $info);
                    }else{
                        
                    }

                }
            ]
        );
        //3-
        \dbsnOOp\add_trace_method(
            "mysqli_stmt",
            "execute",
            [
                "pos_exec" => function (DSSegment $segment, $args, $result, $ex, $that) {
                    list($mysqli) = $args;
                    $segment->name = "mysqli_stmt::execute";
                    $segment->type = Parameter::APP_DATABASE;
                    $info = ObjectMaps::get($this, self::DATABASE_CONFIG_KEY, []);
                    $info[Parameter::QUERY_NUM_ROWS] = $that->affected_rows;
                    $segment->tags = array_merge($segment->tags, $info);
                }
            ]
        );
    }
    private function getMysqliInfo($args)
    {
        $port = "3306";
        list($host,,, $base) = $args;
        if (!empty($host)) {
            $parts = explode(':', $host);
            $host = $parts[0];
            $port = isset($parts[1]) ? $parts[1] : '3306';
        } else {
            $host = "localhost";
        }

        if (empty($base))
            $base = "mysql";


        return [
            Parameter::DB_HOST => $host,
            Parameter::DB_NAME => $base,
            Parameter::DB_PORT => $port,
            Parameter::DB_TYPE => 'mysql'
        ];
    }
}
