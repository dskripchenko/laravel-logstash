<?php

namespace Dskripchenko\LaravelLogstash\Jobs;

use Dskripchenko\LaravelLogstash\Interfaces\LogstashHttpHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class LogstashJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var LogstashHttpHandler
     */
    public LogstashHttpHandler $httpHandler;

    /**
     * LogstashJob constructor.
     * @param  LogstashHttpHandler  $httpHandler
     */
    public function __construct(LogstashHttpHandler $httpHandler)
    {
        $this->httpHandler = $httpHandler;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->httpHandler->sendHttpRequest();
    }
}
