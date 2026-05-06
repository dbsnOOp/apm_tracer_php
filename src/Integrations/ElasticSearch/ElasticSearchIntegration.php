<?php

namespace dbsnOOp\Integrations\ElasticSearch;

use dbsnOOp\DSSegment;
use dbsnOOp\Integrations\Integration;

final class ElasticSearchIntegration extends Integration
{
    public function integrate()
    {
        // Antigo Elasticsearch\Client (v7)
        if (class_exists('Elasticsearch\Client')) {
            \dbsnOOp\add_trace_method(
                'Elasticsearch\Client',
                'search',
                [
                    'pos_exec' => function (DSSegment $segment, $args, $result, $ex, $that) {
                        $segment->type = 'search';
                        $segment->name = 'Elasticsearch::search';
                        $segment->tags['elasticsearch.index'] = isset($args[0]['index']) ? $args[0]['index'] : '';
                    }
                ]
            );
        }

        // Novo Elastic\Elasticsearch\Client (v8)
        if (class_exists('Elastic\Elasticsearch\Client')) {
            \dbsnOOp\add_trace_method(
                'Elastic\Elasticsearch\Client',
                'search',
                [
                    'pos_exec' => function (DSSegment $segment, $args, $result, $ex, $that) {
                        $segment->type = 'search';
                        $segment->name = 'Elasticsearch::search';
                        $segment->tags['elasticsearch.index'] = isset($args[0]['index']) ? $args[0]['index'] : '';
                    }
                ]
            );
        }
    }
}
