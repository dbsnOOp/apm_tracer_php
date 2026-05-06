<?php

namespace dbsnOOp\Integrations\Guzzle;

use dbsnOOp\DSSegment;
use dbsnOOp\Integrations\Integration;

final class GuzzleIntegration extends Integration
{
    public function integrate()
    {
        if (class_exists('GuzzleHttp\Client')) {
            \dbsnOOp\add_trace_method(
                'GuzzleHttp\Client',
                'send',
                [
                    'pre_exec' => function (&$segment, $args, $that) {
                        $request = $args[0] ?? null;
                        if ($request) {
                            $segment->type = 'http_outbound';
                            $segment->name = 'HTTP ' . $request->getMethod();
                            $segment->tags['http.url'] = (string)$request->getUri();
                            $segment->tags['http.method'] = $request->getMethod();
                        }
                    },
                    'pos_exec' => function (DSSegment $segment, $args, $result, $ex, $that) {
                        if ($result) {
                            $segment->tags['http.status_code'] = $result->getStatusCode();
                        }
                    }
                ]
            );
        }
    }
}
