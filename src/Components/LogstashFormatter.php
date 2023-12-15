<?php

namespace Dskripchenko\LaravelLogstash\Components;

use Monolog\Formatter\NormalizerFormatter as BaseLogstashFormatter;
use Monolog\LogRecord;

class LogstashFormatter extends BaseLogstashFormatter
{

    /**
     * @var string
     */
    protected string $systemName;

    /**
     * @var string
     */
    protected string $applicationName;

    /**
     * @var string
     */
    protected string $extraKey;

    /**
     * @var string
     */
    protected string $contextKey;

    /**
     * @param string $applicationName
     * @param string|null $systemName
     * @param string $extraKey
     * @param string $contextKey
     */
    public function __construct(
        string $applicationName,
        ?string $systemName = null,
        string $extraKey = 'extra',
        string $contextKey = 'context'
    ) {
        parent::__construct('Y-m-d\TH:i:s.uP');

        $this->systemName = $systemName ?? (string) gethostname();
        $this->applicationName = $applicationName;
        $this->extraKey = $extraKey;
        $this->contextKey = $contextKey;
    }

    /**
     * @param LogRecord $record
     *
     * @return array
     */
    public function format(LogRecord $record): array
    {
        return $this->prepare($this->normalizeRecord($record));
    }


    /**
     * @param array $data
     *
     * @return array
     */
    protected function prepare(array $data): array
    {
        $message = [
            '@version' => 1,

            '@timestamp' => $data['datetime'] ?? gmdate('c'),
            'DateTime' => date('Y-m-d H:i:s'),

            'Type' => $data['level_name']
                ?? $data['level']
                ?? $this->applicationName
                    ?: $data['channel']
                    ?? 'default',

            'Host' => $this->systemName,
            'Message' => $data['message'] ?? '',
            'Channel' => $data['channel'] ?? '',
            'Data' => []
        ];

        if (isset($data['level_name'])) {
            $message['Level'] = $data['level_name'];
        }

        if (!empty($data['extra'])) {
            foreach ($data['extra'] as $key => $val) {
                $message['Data'][$key] = $val;
            }
        }
        if (!empty($data['context'])) {
            foreach ($data['context'] as $key => $val) {
                $message['Data'][$key] = $val;
            }
        }

        data_set($message, 'Data', $this->toJson($message['Data']));

        $message['tags'] = $message['tags'] ?? [];

        if (isset($record['level_name'])) {
            $message['tags'][] = $record['level_name'];
        }

        if ($this->applicationName) {
            $message['tags'][] = $this->applicationName;
        }

        $data['json'] = $this->toJson($message);

        return $data;
    }

}
