<?php

namespace dbsnOOp\Integrations\Mysqli;

use dbsnOOp\Integrations\Integration;
use dbsnOOp\Integrations\ObjectMaps;
use dbsnOOp\Tracker;

use const dbsnOOp\DB_DRIVER;
use const dbsnOOp\DB_HOST;
use const dbsnOOp\DB_NAME;
use const dbsnOOp\DB_PORT;
use const dbsnOOp\DB_QUERY;
use const dbsnOOp\DB_TYPE;
use const dbsnOOp\QUERY_NUM_ROWS;
use const dbsnOOp\TYPE_APP_DATABASE_CONNECT;
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
                "pos_exec" => function ($args, $result) {
                    if ($result !== false) {
                        $info = $this->getMysqliInfo($args);
                        ObjectMaps::set($result, self::DATABASE_CONFIG_KEY, $info);
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
                "pos_exec" => function ($args, $result) {
                    list($mysqli) = $args;
                    $host = empty($args[1]) ? null : $args[0];
                    $dbName = empty($args[4]) ? null : $args[4];
                    if ($result !== false) {
                        $info = $this->getMysqliInfo([$host, "", "", $dbName]);
                        ObjectMaps::set($mysqli, self::DATABASE_CONFIG_KEY, $info);
                    }
                }
            ]
        );
        //4-
        \dbsnOOp\add_hook_method(
            'mysqli',
            'real_connect',
            [
                "pos_exec" => function ($args, $result) {
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
                "pos_exec" => function ($args, $result) {
                    list($mysqli, $dbName) = $args;
                    $object = ObjectMaps::get($mysqli, self::DATABASE_CONFIG_KEY, []);
                    $object[DB_NAME] = $dbName;
                    ObjectMaps::set($mysqli, self::DATABASE_CONFIG_KEY, $object);
                }
            ]
        );

        //2-
        \dbsnOOp\add_hook_method(
            'mysqli',
            'select_db',
            [
                "pos_exec" => function ($args, $result) {
                    list($dbName) = $args;
                    $object = ObjectMaps::get($this, self::DATABASE_CONFIG_KEY, []);
                    $object[DB_NAME] = $dbName;
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
                "pos_exec" => function ($tracker, $args, $result) {
                    list($mysqli, $query) = $args;
                    $tracker->type = TYPE_APP_DATABASE_QUERY;
                    $info = ObjectMaps::get($mysqli, self::DATABASE_CONFIG_KEY, []);
                    $info[DB_QUERY] = $query;
                    $info[QUERY_NUM_ROWS] = mysqli_num_rows($result);
                    $tracker->info = $info;
                }
            ]
        );
        //2-
        \dbsnOOp\add_hook_function(
            "mysqli_prepare",
            [
                "pos_exec" => function ($args, $result) {
                    list($mysqli, $query) = $args;
                    $info = ObjectMaps::get($mysqli, self::DATABASE_CONFIG_KEY, []);
                    $info[DB_QUERY] = $query;
                    ObjectMaps::set($mysqli, self::DATABASE_CONFIG_KEY, $info);
                }
            ]
        );
        //3-
        \dbsnOOp\add_trace_function(
            "mysqli_stmt_execute",
            [
                "pos_exec" => function ($tracker, $args, $result) {
                    list($mysqli) = $args;
                    $tracker->type = TYPE_APP_DATABASE_QUERY;
                    $info = ObjectMaps::get($mysqli, self::DATABASE_CONFIG_KEY, []);
                    $info[QUERY_NUM_ROWS] = mysqli_stmt_affected_rows($result);
                    $tracker->info = $info;
                }
            ]
        );
        //OO Mode
        //1-
        \dbsnOOp\add_trace_method(
            "mysqli",
            "query",
            [
                "pos_exec" => function ($tracker, $args, $result) {
                    list($query) = $args;
                    $tracker->type = TYPE_APP_DATABASE_QUERY;
                    $info = ObjectMaps::get($this, self::DATABASE_CONFIG_KEY, []);
                    $info[DB_QUERY] = $query;
                    $info[QUERY_NUM_ROWS] = $result->affected_rows;
                    $tracker->info = $info;
                }
            ]
        );
        //2-
        \dbsnOOp\add_hook_method(
            "mysqli",
            "prepare",
            [
                "pos_exec" => function ($args, $result) {
                    list($query) = $args;
                    $info = ObjectMaps::get($this, self::DATABASE_CONFIG_KEY, []);
                    $info[DB_QUERY] = $query;
                    ObjectMaps::set($this, self::DATABASE_CONFIG_KEY, $info);
                }
            ]
        );
        //3-
        \dbsnOOp\add_trace_method(
            "mysqli_stmt",
            "execute",
            [
                "pos_exec" => function ($tracker, $args, $result) {
                    list($mysqli) = $args;
                    $tracker->type = TYPE_APP_DATABASE_QUERY;
                    $info = ObjectMaps::get($mysqli, self::DATABASE_CONFIG_KEY, []);
                    $info[QUERY_NUM_ROWS] = $result->affected_rows;
                    $tracker->info = $info;
                }
            ]
        );
    }
    private function getMysqliInfo($args)
    {
        $port = "3306";
        list($host, , , $base) = $args;
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
            DB_HOST => $host,
            DB_NAME => $base,
            DB_PORT => $port,
            DB_TYPE => 'mysql'
        ];
    }


    /**
     * Given a mysqli instance, it extract an array containing host info.
     *
     * @param $mysqli
     * @return array
     */
    public static function extractHostInfo($mysqli)
    {
        // silence "Property access is not allowed yet" for PHP <= 7.3
        if (@(!isset($mysqli->host_info) || !is_string($mysqli->host_info))) {
            return [];
        }
        $hostInfo = $mysqli->host_info;
        return self::parseHostInfo(substr($hostInfo, 0, strpos($hostInfo, ' ')));
    }

    /**
     * Given a host definition string, it extract an array containing host info.
     *
     * @param string $hostString
     * @return array
     */
    public static function parseHostInfo($hostString)
    {
        if (empty($hostString) || !is_string($hostString)) {
            return [];
        }

        $parts = explode(':', $hostString);
        $host = $parts[0];
        $port = isset($parts[1]) ? $parts[1] : '3306';
        return [
            Tag::DB_TYPE => 'mysql',
            Tag::TARGET_HOST => $host,
            Tag::TARGET_PORT => $port,
        ];
    }
}
