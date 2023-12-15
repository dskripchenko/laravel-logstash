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
use Monolog\Level;
use Monolog\LogRecord;
use RuntimeException;
use DateTimeImmutable;
use Exception;

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
     * @var LogRecord
     */
    protected LogRecord $record;


    /**
     * @param string $url
     * @param string $method
     * @param int|string|Level $level
     * @param bool $bubble
     */
    public function __construct(
        string $url,
        string $method = 'POST' ,
        int|string|Level $level = Level::Debug,
        bool $bubble = true
    )  {
        parent::__construct($level, $bubble);
        $this->url = $url;
        $this->method = $method;
    }

    /**
     * @return array
     */
    public function __serialize(): array
    {
        return [
            'url' => $this->url,
            'method' => $this->method,
            'credentials' => $this->credentials,
            'options' => $this->options,
            'headers' => $this->headers,
            'spare_channel' => $this->spareChannel,
            'level' => $this->level,
            'bubble' => $this->bubble,
            'record' => [
                'message' => $this->record->message,
                'level' => $this->record->level->getName(),
                'channel' => $this->record->channel,
                'datetime' => $this->record->datetime,
                'formatted' => $this->record->formatted
            ]
        ];
    }

    /**
     * @param array $data
     *
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->url = data_get($data, 'url');
        $this->method = data_get($data, 'method');
        $this->credentials = data_get($data, 'credentials');
        $this->options = data_get($data, 'options');
        $this->headers = data_get($data, 'headers');
        $this->spareChannel = data_get($data, 'spare_channel');
        $this->level = data_get($data, 'level');
        $this->bubble = data_get($data, 'bubble');

        $message = data_get($data, 'record.message');
        $level = data_get($data, 'record.level');
        $channel = data_get($data, 'record.channel');
        $context = data_get($data, 'record.formatted.context');
        $extra = data_get($data, 'record.formatted.extra');
        $formatted = data_get($data, 'record.formatted');

        try {
            $datetime = new DateTimeImmutable(data_get($data, 'record.datetime'));
        }
        catch (Exception) {
            $datetime = new DateTimeImmutable();
        }

        $this->record = new LogRecord(
            $datetime,
            $channel,
            Level::fromName($level),
            $message,
            $context,
            $extra,
            $formatted
        );
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
     * @param LogRecord $record
     *
     * @return void
     */
    protected function write(LogRecord $record): void
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
     * @param LogRecord $record
     *
     * @return string
     */
    protected function generateDataStream(LogRecord $record): string
    {
        return (string) data_get($record->formatted, 'json');
    }
}
