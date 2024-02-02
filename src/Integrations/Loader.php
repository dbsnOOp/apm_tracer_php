<?php

namespace dbsnOOp\Integrations;

use dbsnOOp\Integrations\Mysqli\MysqliIntegration;
use dbsnOOp\Integrations\PDO\PDOIntegration;
use dbsnOOp\Integrations\Sohris\SohrisHttpIntegration;

final class Loader
{
    public static function init()
    {
        $integrations = [
            new MysqliIntegration,
            new SohrisHttpIntegration,
            new PDOIntegration
        ];
        foreach($integrations as $integration)
        {
            $integration->integrate();
        }

    }
}
