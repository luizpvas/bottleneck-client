# Bottleneck - Laravel Client

Collect app metrics, server metrics & errors for Laravel applcations.

## Installation

1. Install the composer dependency

```bash
composer require bottleneck/bottleneck-client
```

```php
public function report(\Throwable $exception)
{
    if ($this->shouldReport($exception) && $metrics = app('Bottleneck\Metrics')) {
        $metrics->captureException($exception);
    }

    parent::report($exception);
}
```