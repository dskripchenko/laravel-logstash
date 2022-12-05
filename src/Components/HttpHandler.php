<?php

namespace Dskripchenko\LaravelLogstash\Components;

use Dskripchenko\LaravelLogstash\Jobs\LogstashJob;
use Dskripchenko\LaravelLogstash\Interfaces\LogstashHttpHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use RuntimeException;

class HttpHandler extends AbstractProcessingHandler implements LogstashHttpHandler
{
    /**
     * @var string
     */
    protected string $url;

    /**
     * @var string
     */
    protected string $method;

    /**
     * @var array
     */
    protected array $credentials = [];

    /**
     * @var array
     */
    protected array $options = [
        'verify' => false
    ];

    /**
     * @var string[]
     */
    protected array $headers = [
        'content-type' => 'application/json'
    ];

    /**
     * @var string
     */
    protected string $spareChannel = 'daily';

    /**
     * @var array
     */
    protected array $record;


    /**
     * HttpHandler constructor.
     * @param string $url
     * @param string $method
     * @param int $level
     * @param bool $bubble
     */
    public function __construct(string $url, string $method = 'POST' , int $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->url = $url;
        $this->method = $method;
    }

    /**
     * @param  array  $credentials
     */
    public function setCredentials(array $credentials = []): void
    {
        $this->credentials = $credentials;
    }

    /**
     * @param  string  $channel
     */
    public function setSpareChannel(string $channel): void
    {
        $this->spareChannel = $channel;
    }

    /**
     * @param  array  $record
     */
    protected function write(array $record): void
    {
        $this->record = $record;

        dispatch(new LogstashJob($this));
    }

    /**
     * @return void
     * @throws GuzzleException
     */
    public function sendHttpRequest(): void
    {
        try {
            $data = $this->generateDataStream($this->record);
            $options = array_merge($this->options, ['auth' => $this->credentials]);
            $client = new Client($options);

            $request = new Request(
                $this->method,
                $this->url,
                $this->headers,
                $data
            );

            $response = $client->send($request);

            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException('Failed logging attempt');
            }
        }
        catch (RuntimeException $e) {
            Log::channel($this->spareChannel)->error($e->getMessage(), $e->getTrace());

            $level = Arr::get($this->record, 'level_name', 'debug');
            $level = strtolower($level);
            $message = Arr::get($this->record, 'message');
            $context = Arr::get($this->record, 'context', []);
            Log::channel($this->spareChannel)->log($level, $message, $context);
        }
    }

    /**
     * @param  array  $record
     * @return string
     */
    protected function generateDataStream(array $record): string
    {
        return (string) $record['formatted'];
    }
}
