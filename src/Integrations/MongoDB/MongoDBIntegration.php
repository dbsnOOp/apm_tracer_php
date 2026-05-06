<?php

namespace dbsnOOp\Integrations\MongoDB;

use dbsnOOp\DSSegment;
use dbsnOOp\Integrations\Integration;

final class MongoDBIntegration extends Integration
{
    public function integrate()
    {
        if (class_exists('MongoDB\Driver\Manager')) {
            \dbsnOOp\add_trace_method(
                'MongoDB\Driver\Manager',
                'executeCommand',
                [
                    'pos_exec' => function (DSSegment $segment, $args, $result, $ex, $that) {
                        $segment->type = 'datastore';
                        $segment->name = 'MongoDB::executeCommand';
                        $segment->tags['db.system'] = 'mongodb';
                        $segment->tags['db.name'] = $args[0] ?? '';
                    }
                ]
            );

            \dbsnOOp\add_trace_method(
                'MongoDB\Driver\Manager',
                'executeQuery',
                [
                    'pos_exec' => function (DSSegment $segment, $args, $result, $ex, $that) {
                        $segment->type = 'datastore';
                        $segment->name = 'MongoDB::executeQuery';
                        $segment->tags['db.system'] = 'mongodb';
                        $segment->tags['db.name'] = $args[0] ?? '';
                        $segment->tags['db.collection'] = $args[1] ?? '';
                    }
                ]
            );
        }
    }
}
