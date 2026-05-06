<?php

namespace dbsnOOp\Integrations;

use dbsnOOp\Integrations\Mysqli\MysqliIntegration;
use dbsnOOp\Integrations\PDO\PDOIntegration;
use dbsnOOp\Integrations\Sohris\SohrisEventsIntegration;
use dbsnOOp\Integrations\Sohris\SohrisHttpIntegration;
use dbsnOOp\Integrations\Sohris\SohrisMysqlIntegration;

final class Loader
{
    public static function init()
    {
        $integrations = [
            new MysqliIntegration,
            new SohrisHttpIntegration,
            new SohrisMysqlIntegration,
            new SohrisEventsIntegration,
            new PDOIntegration,
            new \dbsnOOp\Integrations\Redis\RedisIntegration,
            new \dbsnOOp\Integrations\Memcached\MemcachedIntegration,
            new \dbsnOOp\Integrations\MongoDB\MongoDBIntegration,
            new \dbsnOOp\Integrations\ElasticSearch\ElasticSearchIntegration,
            new \dbsnOOp\Integrations\Guzzle\GuzzleIntegration,
            new \dbsnOOp\Integrations\Laravel\LaravelIntegration,
            new \dbsnOOp\Integrations\Symfony\SymfonyIntegration,
            new \dbsnOOp\Integrations\Zend\ZendIntegration,
            new \dbsnOOp\Integrations\PostgreSql\PostgreSqlIntegration,
            new \dbsnOOp\Integrations\Oracle\OracleIntegration,
            new \dbsnOOp\Integrations\Mssql\MssqlIntegration
        ];
        foreach($integrations as $integration)
        {
            $integration->integrate();
        }

    }
}
