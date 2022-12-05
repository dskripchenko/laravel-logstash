<?php

namespace Dskripchenko\LaravelLogstash\Interfaces;


interface LogstashHttpHandler
{
    public function sendHttpRequest(): void;
}
