<?php

namespace Bottleneck;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class UploadMetrics extends Command
{
    /**
     * Name of the console command.
     * 
     * @var string
     */
    protected $signature = 'bottleneck:upload {--once}';

    /**
     * Console command description.
     * 
     * @var string
     */
    protected $description = 'Sends cached metrics to the Bottleneck service.';

    /**
     * Sends the collected data to the server.
     * 
     * @return void
     */
    function handle()
    {
        $this->uploadServerStats();

        if ($this->option('once')) {
            $this->uploadCachedMetrics();
        } else {
            $interval = intval(config('bottleneck.upload_cached_metrics_interval_in_seconds', 10));

            $startTime = microtime(true);
            while (microtime(true) - $startTime > 60 - $interval) {
                $this->uploadCachedMetrics();
                sleep($interval);
            }
        }
    }

    /**
     * Reads and uploads server metrics such as disk usage, memory usage and CPU usage.
     * 
     * @return void
     */
    function uploadServerStats()
    {
        $stats = new ServerStats();

        $endpoint = config('bottleneck.endpoint', 'https://bottleneck-metrics.com');
        Http::post($endpoint . '/api/server_stats', [
            'private_key'           => config('bottleneck.private_key'),
            'recorded_at'           => intval(microtime(true) * 1000 * 1000),
            'disk_usage_percentage' => $stats->getDiskUsagePercentage(),
            'memory_usage_mb'       => $stats->getMemoryUsageMb(),
            'load_average'          => $stats->getLoadAverage(),
        ]);
    }

    /**
     * Reads the metrics stored in Redis and clears it (like pop operation).
     * Then the data is compressed and uploaded to the Bottleneck service.
     * 
     * @return void
     */
    function uploadCachedMetrics()
    {
        $redisKey = config('bottleneck.redis_key', 'bottleneck-cached-metrics');

        $metricsList = Redis::lrange($redisKey, 0, -1);
        Redis::del($redisKey);

        $compressedMetricsList = base64_encode(gzencode(implode(PHP_EOL, $metricsList)));

        $endpoint = config('bottleneck.endpoint', 'https://bottleneck-metrics.com');
        Http::post($endpoint . '/api/metrics', [
            'private_key' => config('bottleneck.private_key'),
            'metrics' => $compressedMetricsList
        ]);
    }
}
