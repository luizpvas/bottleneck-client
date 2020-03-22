<?php

namespace Tests;

use Illuminate\Support\Facades\Redis;

class CollectMetricsTest extends TestCase
{
    function setUp(): void
    {
        parent::setUp();
        Redis::del('bottleneck-cached-metrics');
        $this->get('/posts')->assertStatus(200);
    }

    /** @test */
    function tracks_start_and_end_time_of_the_request()
    {
        $metrics = app('Bottleneck\Metrics');
        $duration = $metrics->requestEnd - $metrics->requestStart;
        $this->assertGreaterThan(0.1, $duration);
    }

    /** @test */
    function tracks_database_queries()
    {
        $metrics = app('Bottleneck\Metrics');
        $this->assertGreaterThan(3, count($metrics->databaseQueries));
    }

    /** @test */
    function tracks_cache_queries()
    {
        $metrics = app('Bottleneck\Metrics');
        $this->assertEquals(2, count($metrics->cacheQueries));
    }

    /** @test */
    function inserts_metrics_in_redis()
    {
        $items = Redis::lrange('bottleneck-cached-metrics', 0, -1);
        $this->assertEquals(1, count($items));
    }
}
