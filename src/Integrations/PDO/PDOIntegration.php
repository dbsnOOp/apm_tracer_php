<?php

namespace dbsnOOp\Integrations\PDO;

use dbsnOOp\DSSegment;
use dbsnOOp\Integrations\Integration;
use dbsnOOp\Integrations\ObjectMaps;
use dbsnOOp\Tracker;
use dbsnOOp\Utils\Parameter;

use const dbsnOOp\DB_HOST;
use const dbsnOOp\DB_NAME;
use const dbsnOOp\DB_PORT;
use const dbsnOOp\DB_QUERY;
use const dbsnOOp\DB_TRANSACTION;
use const dbsnOOp\DB_TYPE;
use const dbsnOOp\DB_VERSION;
use const dbsnOOp\QUERY_NUM_ROWS;
use const dbsnOOp\TYPE_APP_DATABASE_CONNECT;
use const dbsnOOp\TYPE_APP_DATABASE_QUERY;

final class PDOIntegration extends Integration
{

    const DATABASE_CONFIG_KEY = "database_config_key";
    const DATABASE_STM_KEY = "database_statement_key";

    public function __construct()
    {
    }

    public function integrate()
    {

        \dbsnOOp\add_trace_method(
            "PDO",
            "query",
            [
                "pos_exec" => function (DSSegment $segment, $args, $result, $ex, $that) {                    
                    list($query) = $args;
                    $segment->type = Parameter::APP_DATABASE;
                    $segment->name = "PDO::query";
                    $info = ObjectMaps::get($that, self::DATABASE_CONFIG_KEY, []);
                    if (empty($info)) {
                        $port = "3306";
                        $info = $that->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
                        $server_version = $that->getAttribute(\PDO::ATTR_SERVER_VERSION);
                        $driver = $that->getAttribute(\PDO::ATTR_DRIVER_NAME);
                        if (!empty($info)) {
                            $exp_info = explode(" ", $info);
                            $exp_info = explode(":", $exp_info[0]);
                            $host = $exp_info[0];
                            $port = isset($parts[1]) ? $parts[1] : '3306';
                        } else {
                            $host = "localhost";
                        }
                        if (empty($base))
                            $base = "mysql";

                        $info = [
                            Parameter::DB_HOST => $host,
                            Parameter::DB_NAME => $base,
                            Parameter::DB_PORT => $port,
                            Parameter::DB_TYPE => $driver,
                            Parameter::DB_VERSION => $server_version
                        ];
                        ObjectMaps::set($that, self::DATABASE_CONFIG_KEY, $info);
                    }

                    if ($result) {
                        $info[Parameter::QUERY_NUM_ROWS] = $result->rowCount();
                    }
                    $info[Parameter::DB_TRANSACTION] = $that->inTransaction();
                    $info[Parameter::DB_QUERY] = $query;
                    $segment->tags = array_merge($segment->tags, $info);
                }
            ]
        );

        \dbsnOOp\add_trace_method(
            "PDO",
            "exec",
            [
                "pos_exec" => function (DSSegment $segment, $args, $result, $ex, $that) {
                    list($query) = $args;
                    $segment->name = "PDO::exec";
                    $segment->type = Parameter::APP_DATABASE;
                    $info = ObjectMaps::get($that, self::DATABASE_CONFIG_KEY, []);
                    if (empty($info)) {
                        $port = "3306";
                        $info = $that->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
                        $server_version = $that->getAttribute(\PDO::ATTR_SERVER_VERSION);
                        $driver = $that->getAttribute(\PDO::ATTR_DRIVER_NAME);
                        if (!empty($info)) {
                            $exp_info = explode(" ", $info);
                            $exp_info = explode(":", $exp_info[0]);
                            $host = $exp_info[0];
                            $port = isset($parts[1]) ? $parts[1] : '3306';
                        } else {
                            $host = "localhost";
                        }
                        if (empty($base))
                            $base = "mysql";

                        $info = [
                            Parameter::DB_HOST => $host,
                            Parameter::DB_NAME => $base,
                            Parameter::DB_PORT => $port,
                            Parameter::DB_TYPE => $driver,
                            Parameter::DB_VERSION => $server_version
                        ];
                        ObjectMaps::set($that, self::DATABASE_CONFIG_KEY, $info);
                    }

                    $info[Parameter::DB_TRANSACTION] = $that->inTransaction();
                    $info[Parameter::QUERY_NUM_ROWS] = $result;
                    $info[Parameter::DB_QUERY] = $query;
                    $segment->tags = array_merge($segment->tags, $info);
                }
            ]
        );

        \dbsnOOp\add_trace_method(
            "PDOStatement",
            "execute",
            [
                "pos_exec" => function (DSSegment $segment, $args, $result, $ex, $that) {
                    $pdo = ObjectMaps::get($that, self::DATABASE_STM_KEY, null);
                    if (!$pdo) {
                        return;
                    }
                    $segment->type = Parameter::APP_DATABASE;
                    $segment->name = "PDOStatement::execute";
                    $info = ObjectMaps::get($pdo, self::DATABASE_CONFIG_KEY, []);
                    if (empty($info)) {
                        $port = "3306";
                        $info = $pdo->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
                        $server_version = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
                        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
                        if (!empty($info)) {
                            $exp_info = explode(" ", $info);
                            $exp_info = explode(":", $exp_info[0]);
                            $host = $exp_info[0];
                            $port = isset($parts[1]) ? $parts[1] : '3306';
                        } else {
                            $host = "localhost";
                        }
                        if (empty($base))
                            $base = "mysql";

                        $info = [
                            Parameter::DB_HOST => $host,
                            Parameter::DB_NAME => $base,
                            Parameter::DB_PORT => $port,
                            Parameter::DB_TYPE => $driver,
                            Parameter::DB_VERSION => $server_version
                        ];
                        ObjectMaps::set($pdo, self::DATABASE_CONFIG_KEY, $info);
                    }
                    $info[Parameter::DB_TRANSACTION] = $pdo->inTransaction();
                    $info[Parameter::QUERY_NUM_ROWS] = $that->rowCount();
                    $info[Parameter::DB_QUERY] = $that->queryString;
                    $segment->tags = array_merge($segment->tags, $info);
                }
            ]
        );

        \dbsnOOp\add_hook_method(
            "PDO",
            "prepare",
            [
                "pos_exec" => function (DSSegment $segment, $args, $result, $ex, $that) {
                    if (!$result) return;
                    list($query) = $args;
                    $info = ObjectMaps::get($that, self::DATABASE_CONFIG_KEY, []);
                    if (empty($info)) {
                        $port = "3306";
                        $info = $that->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
                        $server_version = $that->getAttribute(\PDO::ATTR_SERVER_VERSION);
                        $driver = $that->getAttribute(\PDO::ATTR_DRIVER_NAME);
                        if (!empty($info)) {
                            $exp_info = explode(" ", $info);
                            $exp_info = explode(":", $exp_info[0]);
                            $host = $exp_info[0];
                            $port = isset($parts[1]) ? $parts[1] : '3306';
                        } else {
                            $host = "localhost";
                        }
                        if (empty($base))
                            $base = "mysql";

                        $info = [
                            Parameter::DB_HOST => $host,
                            Parameter::DB_NAME => $base,
                            Parameter::DB_PORT => $port,
                            Parameter::DB_TYPE => $driver,
                            Parameter::DB_VERSION => $server_version
                        ];
                        ObjectMaps::set($that, self::DATABASE_CONFIG_KEY, $info);
                    }
                    ObjectMaps::set($result, self::DATABASE_STM_KEY, $that);
                }
            ]
        );
    }
}
