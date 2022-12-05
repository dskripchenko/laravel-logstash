<?php

use Dskripchenko\LaravelLogstash\Components\LogstashLogger;

return [
    'channels' => [
        'logstash' => [
            'driver' => 'custom',
            'via' => LogstashLogger::class,
            'protocol' => env('LOG_LOGSTASH_PROTOCOL'),
            'host' => env('LOG_LOGSTASH_HOST'),
            'port' => env('LOG_LOGSTASH_PORT'),
            'path' => env('LOG_LOGSTASH_PATH'),
            'credentials' => [
                'user' => env('LOG_LOGSTASH_USER'),
                'password' => env('LOG_LOGSTASH_PASSWORD'),
            ],
            'spare_channel' => env('LOG_LOGSTASH_SPARE_CHANNEL', 'daily'),
        ]
    ],

];
