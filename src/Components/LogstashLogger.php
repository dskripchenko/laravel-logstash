<?php

namespace Dskripchenko\LaravelLogstash\Components;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class LogstashLogger
{
    /**
     * @param  array  $config
     * @return LoggerInterface
     */
    public function __invoke(array $config): LoggerInterface
    {
        $handler = $this->getHandler($config);
        $handler->setFormatter($this->getFormatter());
        return new Logger($this->getLoggerName(), [$handler]);
    }

    /**
     * @return string
     */
    public function getLoggerName(): string
    {
        return 'logstash';
    }

    /**
     * @param  array  $config
     * @return HandlerInterface
     */
    protected function getHandler(array $config): HandlerInterface
    {
        $handler = new HttpHandler($this->getUrl($config), $this->getMethod($config));
        $handler->setSpareChannel($this->getSpareChannel($config));
        $handler->setCredentials($this->getCredentials($config));
        return $handler;
    }

    /**
     * @param  array  $config
     * @return string
     */
    protected function getUrl(array $config): string
    {
        $protocol = $config['protocol'] ?? 'http';
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?: false;
        $path = trim($config['path'] ?? '', '/');

        $url = "{$protocol}://{$host}";
        if ($port) {
            $url = "{$url}:{$port}";
        }
        return "{$url}/{$path}";
    }

    /**
     * @param  array  $config
     * @return string
     */
    protected function getMethod(array $config): string
    {
        return $config['method'] ?? 'POST';
    }

    /**
     * @param  array  $config
     * @return string
     */
    protected function getSpareChannel(array $config): string
    {
        return $config['spare_channel'] ?? 'daily';
    }

    /**
     * @param  array  $config
     * @return array
     */
    protected function getCredentials(array $config): array
    {
        $user = $config['credentials']['user'] ?? false;
        $password = $config['credentials']['password'] ?? false;
        if ($user && $password) {
            return [$user, $password];
        }
        return [];
    }

    /**
     * @return FormatterInterface
     */
    protected function getFormatter(): FormatterInterface
    {
        return new LogstashFormatter(
            config('app.name')
        );
    }

}
