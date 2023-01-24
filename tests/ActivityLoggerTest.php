<?php

namespace LukeTowers\EasyAudit\Tests;

use Config;
use Google\Service\CloudSourceRepositories\Repo;
use LukeTowers\EasyAudit\Classes\ActivityLogger;
use LukeTowers\EasyAudit\Models\Activity;
use System\Models\RequestLog;
use System\Tests\Bootstrap\PluginTestCase;
use Winter\Storm\Database\Builder;
use Winter\Storm\Exception\SystemException;

class ActivityLoggerTest extends PluginTestCase
{
    protected $logger = null;

    protected ?RequestLog $model = null;

    public function setUp(): void
    {
        parent::setUp();

        if (!$this->model) {
            $this->model = RequestLog::create([
                'url' => 'http://example.com',
                'status_code' => 200,
            ]);
        }

        $this->logger = new ActivityLogger();
    }

    /**
     * Generate log data for testing
     */
    protected function getLogData(array $override = []): array
    {
        return array_merge(
            [
                'event' => (string) time(),
                'description' => null,
                'subject' => $this->model,
                'source' => $this->model,
                'properties' => ['foo' => 'bar'],
                'connection' => Config::get('database.default'),
                'logName' => 'ActivityLoggerTest',
            ],
            $override
        );
    }

    /**
     * Build a Query Builder targeting the provided data
     */
    protected function getQuery(array $data): Builder
    {
        $q = $data['connection'] ? Activity::on($data['connection']) : Activity::query();

        if (!empty($data['logName'])) {
            $q->where('log', $data['logName']);
        }

        if (!empty($data['event'])) {
            $q->where('event', $data['event']);
        }

        if (!empty($data['description'])) {
            $q->where('description', $data['description']);
        }

        if (!empty($data['subject'])) {
            $q->where('subject_type', get_class($data['subject']));
            $q->where('subject_id', $data['subject']->getKey());
        }

        if (!empty($data['source'])) {
            $q->where('source_type', get_class($data['source']));
            $q->where('source_id', $data['source']->getKey());
        }

        // @TODO: Properties array is automatically appended to if user agent
        // tracking is enabled, need to do this better
        // if (!empty($data['properties'])) {
        //     $q->where('properties', json_encode($data['properties']));
        // }

        return $q;
    }

    /**
     * Assert that the provided Activity record contains the provided data
     * that would have been used to construct it
     */
    protected function assertRecordMatchesData(Activity $record, array $data)
    {
        $inputData = [];
        $recordData = [];

        foreach ($data as $key => $value) {
            switch ($key) {
                case 'connection':
                    $inputData['connection'] = $value;
                    $recordData['connection'] = $record->getConnectionName();
                    break;
                case 'logName':
                    $inputData['log'] = $value;
                    $recordData['log'] = $record->log;
                    break;
                case 'subject':
                case 'source':
                    $inputData[$key.'_type'] = get_class($value);
                    $inputData[$key.'_id'] = $value->getKey();
                    $recordData[$key.'_type'] = $record->{$key} ? get_class($record->{$key}) : null;
                    $recordData[$key.'_id'] = $record->{$key} ? $record->{$key}->getKey() : null;
                    break;
                case 'properties':
                    $inputData[$key] = $value;
                    $recordData[$key] = is_array($record->{$key}) ? array_only($record->{$key}, array_keys($value)) : $record->{$key};
                    break;
                default:
                    $inputData[$key] = $value;
                    $recordData[$key] = $record->{$key};
                    break;
            }
        }

        $this->assertEquals($recordData, $inputData);
    }

    public function testLoggerRequiresEvent()
    {
        $this->expectException(SystemException::class);
        $this->logger->log();
    }

    public function testLoggerRequestCache()
    {
        $data = $this->getLogData(['logName' => __FUNCTION__]);

        $this->logger->requestActivityCache(true);
        $this->logger->log($data);
        $this->logger->log($data);

        $this->assertEquals(1, $this->getQuery(array_only($data, ['event', 'logName', 'connection']))->count());

        $this->logger->requestActivityCache(false);
        $this->logger->log($data);

        $this->assertEquals(2, $this->getQuery(array_only($data, ['event', 'logName', 'connection']))->count());
    }

    public function testAllPropertiesLogged()
    {
        $data = $this->getLogData(['logName' => __FUNCTION__]);

        $this->logger->log($data);

        $this->assertRecordMatchesData($this->getQuery($data)->firstOrFail(), $data);
    }

    public function testLoggingOnConnection()
    {
        $data = $this->getLogData([
            'event' => (string) time(),
            'logName' => __FUNCTION__,
            'connection' => Config::get('database.default'),
        ]);

        // Log data in the connection
        $this->logger->log($data);
        $this->assertRecordMatchesData($this->getQuery($data)->firstOrFail(), $data);
    }
}
