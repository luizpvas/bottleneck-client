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
     * List of events, in order they happened.
     * 
     * @var array
     */
    public $events;

    /**
     * Additional context about the request, such as who is the current user,
     * the current project, stuff lik this.
     * 
     * @var array
     */
    public $context = [];

    function __construct()
    {
        $this->events = collect([]);
    }

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

    /**
     * Pushes the event in the events list.
     * 
     * @param  array $eventData
     * @return void
     */
    function addEvent($type, $eventData)
    {
        $this->events->push([
            'type' => $type,
            'data' => $eventData,
            'recorded_at' => intval(microtime(true) * 1000 * 1000)
        ]);
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

        if ($response instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            $this->responseSizeInBytes = strlen($response->content());
        } else {
            $this->responseSizeInBytes = null;
        }

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

    /**
     * At least one exception in the events list.
     * 
     * @return boolean
     */
    function hasException()
    {
        return $this->events->contains(function ($event) {
            return $event['type'] == 'exception';
        });
    }

    function toArray()
    {
        return [
            'context'                 => $this->context,
            'request_start'           => $this->requestStart,
            'request_end'             => $this->requestEnd,
            'response_size_in_bytes'  => $this->responseSizeInBytes,
            'response_status_code'    => $this->responseStatusCode,
            'events'                  => $this->events,
        ];
    }
}
