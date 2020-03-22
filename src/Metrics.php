<?php

namespace Bottleneck;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class Metrics
{
    /**
     * Moment the request was reiceved, as a timestamp with millisecond precision.
     * Only relevant for requests, ignored when running a job queue.
     * 
     * @var integer|null
     */
    public $requestStart;

    /**
     * Moment the request finished processing, but not sent yet.
     * Only relevant for requests, ignored when running a job queue.
     * 
     * @var integer|null
     */
    public $requestEnd;

    /**
     * Size of the response sent in this request.
     * 
     * @var integer|null
     */
    public $responseSizeInBytes;

    /**
     * HTTP status code the request sent to the user.
     * 
     * @var integer|null
     */
    public $responseStatusCode;

    /**
     * List of database queries ran in this request/job.
     * 
     * @var array
     */
    public $databaseQueries = [];

    /**
     * List of cache queries ran in this request/job.
     * 
     * @var array
     */
    public $cacheQueries = [];

    /**
     * Additional context about the request, such as who is the current user,
     * the current project, stuff lik this.
     * 
     * @var array
     */
    public $context = [];

    /**
     * Exception of the request, only set if something bad happened.
     * 
     * @var \Throwable|null
     */
    public $exception;

    /**
     * Adds a piece of context to the metrics.
     * 
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    function addContext($key, $value)
    {
        if (!is_string($value) && !is_numeric($value)) {
            throw new \InvalidArgumentException('[Bottleneck] Context value must be string or number.');
        }

        $this->context[$key] = $value;
    }

    function addDatabaseQuery($sql, $duration)
    {
        $this->databaseQueries[] = [
            'sql' => $sql,
            'duration' => $duration,
            'finished_at' => intval(microtime(true) * 1000 * 1000)
        ];
    }

    function addCacheQuery($key, $hit)
    {
        $this->cacheQueries[] = [
            'key' => $key,
            'hit' => $hit,
            'finished_at' => intval(microtime(true) * 1000 * 1000)
        ];
    }

    /**
     * Something bad hapenned.
     * 
     * @param  \Throwable $exception
     * @return void
     */
    function captureException($exception)
    {
        $this->exception = $exception;
    }

    /**
     * The request finished processing and the response was sent.
     * 
     * @param  Illuminate\Http\Response $response
     * @return void
     */
    function responseSent($response)
    {
        $this->requestStart = intval(LARAVEL_START * 1000 * 1000);
        $this->requestEnd = intval(microtime(true) * 1000 * 1000);
        $this->responseSizeInBytes = strlen($response->content());
        $this->responseStatusCode = $response->status();

        if ($this->hasException()) {
            $this->uploadNow();
        } else {
            $this->pushToQueue();
        }
    }

    /**
     * Upload to Bottleneck immediatly in case of exceptions.
     * 
     * @return void
     */
    public function uploadNow()
    {
        $endpoint = config('bottleneck.endpoint', 'https://bottleneck-metrics.com');

        Http::post($endpoint . '/api/exceptions', [
            'api_key' => config('bottleneck.api_key'),
            'metrics' => $this->toArray()
        ]);
    }

    /**
     * Pushes the stored metrics to Redis so we can upload it later on. This caching happens for
     * performance reasons both on our end and our customers end.
     * 
     * @return void
     */
    function pushToQueue()
    {
        Redis::rpush(config('bottleneck.redis_key', 'bottleneck-cached-metrics'), json_encode($this->toArray()));
    }

    function hasException()
    {
        return !is_null($this->exception);
    }


    function toArray()
    {
        return [
            'context'                 => $this->context,
            'request_start'           => $this->requestStart,
            'request_end'             => $this->requestEnd,
            'response_size_in_bytes'  => $this->responseSizeInBytes,
            'response_status_code'    => $this->responseStatusCode,
            'database_queries'        => $this->databaseQueries,
            'cache_queries'           => $this->cacheQueries,
            'exception'               => $this->exception
        ];
    }
}
