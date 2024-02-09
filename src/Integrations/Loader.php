<?php

namespace dbsnOOp\Integrations;

use dbsnOOp\Integrations\Mysqli\MysqliIntegration;
use dbsnOOp\Integrations\PDO\PDOIntegration;
use dbsnOOp\Integrations\Sohris\SohrisHttpIntegration;
use dbsnOOp\Integrations\Sohris\SohrisMysqlIntegration;

final class Loader
{
    public static function init()
    {
        $integrations = [
            new MysqliIntegration,
            new SohrisHttpIntegration,
            new SohrisMysqlIntegration ,
            new PDOIntegration
        ];
        foreach($integrations as $integration)
        {
            $integration->integrate();
        }

    }
}
