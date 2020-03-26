<?php

namespace Bottleneck;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class BottleneckClientServiceProvider extends ServiceProvider
{
    /**
     * Global metrics reference, so we don't have to resolve it every time.
     * 
     * @var Bottleneck\Metrics
     */
    protected $metrics;

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
            __DIR__ . '/../config/bottleneck.php' => config_path('bottleneck.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                UploadMetrics::class
            ]);
        }

        $this->metrics = $this->app->make(Metrics::class);

        $this->app['events']->listen(MessageLogged::class, [$this, 'recordException']);

        $this->app['events']->listen(QueryExecuted::class, function (QueryExecuted $event) {
            $this->metrics->addEvent('query', [
                'sql'  => $event->sql,
                'time' => $event->time
            ]);
        });

        $this->app['events']->listen(CacheHit::class, function (CacheHit $event) {
            $this->metrics->addEvent('cache', [
                'key'  => $event->key,
                'hit'  => true
            ]);
        });

        $this->app['events']->listen(CacheMissed::class, function (CacheMissed $event) {
            $this->metrics->addEvent('cache', [
                'key'  => $event->key,
                'hit'  => false
            ]);
        });
    }

    function recordException(MessageLogged $event)
    {
        $shouldIgnore = !isset($event->context['exception']) || !$event->context['exception'] instanceof \Exception;

        if ($shouldIgnore) {
            return;
        }

        $exception = $event->context['exception'];

        $trace = collect($exception->getTrace())->map(function ($item) {
            return Arr::only($item, ['file', 'line']);
        })->toArray();

        $this->metrics->addEvent('exception', [
            'class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'message' => $exception->getMessage(),
            'trace' => $trace,
            'line_preview' => ExceptionContext::get($exception)
        ]);
    }
}
