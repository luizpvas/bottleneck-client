<?php

return [
    'private_key' => env('BOTTLENECK_PRIVATE_KEY'),
    'endpoint' => env('BOTTLENECK_ENDPOINT', 'https://bottleneck-metrics.com'),
    'redis_key' => env('BOTTLENECK_REDIS_KEY', 'bottleneck-cached-metrics'),
    'upload_cached_metrics_interval_in_seconds' => env('BOTTLENECK_UPLOAD_INTERVAL', 10),
];
