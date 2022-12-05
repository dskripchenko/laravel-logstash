<?php

namespace Dskripchenko\LaravelLogstash\Components;

use Monolog\Formatter\LogstashFormatter as BaseLogstashFormatter;

class LogstashFormatter extends BaseLogstashFormatter
{
    /**
     * @param array $record
     * @return string
     */
    public function format(array $record): string
    {
        $record = (array) $this->normalize($record);
        $message = $this->prepare($record);
        return $this->toJson($message) . "\n";
    }


    /**
     * @param array $record
     * @return array
     */
    protected function prepare(array $record): array
    {
        $message = [
            '@version' => 1,

            '@timestamp' => $record['datetime'] ?? gmdate('c'),
            'DateTime' => date('Y-m-d H:i:s'),

            'Type' => $record['level_name']
                ?? $record['level']
                ?? $this->applicationName
                    ?: $record['channel']
                    ?? 'default',

            'Host' => $this->systemName,
            'Message' => $record['message'] ?? '',
            'Channel' => $record['channel'] ?? '',
        ];

        if (isset($record['level_name'])) {
            $message['Level'] = $record['level_name'];
        }

        if (!empty($record['extra'])) {
            foreach ($record['extra'] as $key => $val) {
                $message['Data'][$key] = $val;
            }
        }
        if (!empty($record['context'])) {
            foreach ($record['context'] as $key => $val) {
                $message['Data'][$key] = $val;
            }
        }

        if (data_get($message, 'Data')) {
            $json = json_encode($message['Data']);
            data_set($message, 'Data', $json);
        }

        $message['tags'] = $message['tags'] ?? [];

        if (isset($record['level_name'])) {
            $message['tags'][] = $record['level_name'];
        }

        if ($this->applicationName) {
            $message['tags'][] = $this->applicationName;
        }

        return $message;
    }

}
