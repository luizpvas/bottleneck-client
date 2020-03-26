<?php

namespace Tests;

use Illuminate\Support\Facades\Http;

class ErrorTrackingTest extends TestCase
{
    function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    /** @test */
    function tracks_request_error()
    {
        $this->get('/errors')->assertStatus(500);

        $metrics = app('Bottleneck\Metrics');

        $exceptions = $metrics->events->filter(function ($event) {
            return $event['type'] == 'exception';
        });

        $this->assertEquals(1, count($exceptions));
        $this->assertEquals(500, $metrics->responseStatusCode);
    }

    /** @test */
    function uploads_to_server_immediately()
    {
        $this->get('/errors')->assertStatus(500);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://bottleneck-metrics.com/api/exceptions';
        });
    }
}
