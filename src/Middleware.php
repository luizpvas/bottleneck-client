<?php

namespace Bottleneck;

use Closure;

class Middleware
{
    function handle($request, Closure $next)
    {
        if (!defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        return $next($request);
    }

    function terminate($request, $response)
    {
        $metrics = app('Bottleneck\Metrics');
        $metrics->responseSent($response);
    }
}
