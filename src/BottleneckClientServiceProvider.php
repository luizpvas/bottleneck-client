<?php

namespace Bottleneck;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class BottleneckClientServiceProvider extends ServiceProvider
{
    /**
     * Register bottleneck services.
     *
     * @return void
     */
    function register()
    {
        $this->app->instance(Metrics::class, new Metrics());
    }

    /**
     * Boots up event listeners for various things that happens during a request or queue job,
     * such as database queries, cache queries and others.
     * 
     * @return void
     */
    function boot()
    {
        $this->publishes([
            __DIR__ . '../config/bottleneck.php' => config_path('bottleneck.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                UploadMetrics::class
            ]);
        }

        $metrics = $this->app->make(Metrics::class);

        DB::listen(function ($query) use ($metrics) {
            $metrics->addDatabaseQuery($query->sql, $query->time);
        });

        Event::listen('Illuminate\Cache\Events\CacheHit', function ($event) use ($metrics) {
            $metrics->addCacheQuery($event->key, true);
        });

        Event::listen('Illuminate\Cache\Events\CacheMissed', function ($event) use ($metrics) {
            $metrics->addCacheQuery($event->key, false);
        });
    }
}
