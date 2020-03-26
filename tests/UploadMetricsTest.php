<?php

namespace Tests;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class UploadMetricsTest extends TestCase
{
    function setUp(): void
    {
        parent::setUp();
        Redis::del('bottleneck-cached-metrics');
    }

    /** @test */
    function uploads_cached_metrics()
    {
        Http::fake();

        $this->get('/posts')->assertStatus(200);
        $this->get('/posts')->assertStatus(200);
        $this->get('/posts')->assertStatus(200);

        $this->artisan('bottleneck:upload --once');

        Http::assertSent(function ($request) {
            if ($request->url() == 'https://bottleneck-metrics.com/api/metrics') {
                // file_put_contents('compressed-metrics.txt', $request['metrics']);
                $records = explode(PHP_EOL, gzdecode(base64_decode($request['metrics'])));
                return count($records) == 3;
            }
        });
    }
}
