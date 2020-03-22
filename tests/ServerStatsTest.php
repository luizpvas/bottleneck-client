<?php

namespace Tests;

use Illuminate\Support\Facades\Http;

class ServerStatsTest extends TestCase
{
    /** @test */
    function collect_server_stats()
    {
        Http::fake();

        $this->artisan('bottleneck:upload --once');

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() == 'https://bottleneck-metrics.com/api/server_stats'
                && $data['disk_usage_percentage']
                && $data['memory_usage_mb']
                && $data['load_average'];
        });
    }
}
