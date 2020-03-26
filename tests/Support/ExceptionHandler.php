<?php

namespace Tests\Support;

use Illuminate\Foundation\Exceptions\Handler;

class ExceptionHandler extends Handler
{
    public function report(\Throwable $exception)
    {
        // if ($this->shouldReport($exception) && $metrics = app('Bottleneck\Metrics')) {
        //     $metrics->captureException($exception);
        // }

        parent::report($exception);
    }

    public function render($request, \Throwable $exception)
    {
        return parent::render($request, $exception);
    }
}
