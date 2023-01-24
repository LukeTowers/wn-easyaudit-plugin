<?php

namespace LukeTowers\EasyAudit\Tests;

use LukeTowers\EasyAudit\Models\Activity;
use System\Models\RequestLog;
use System\Tests\Bootstrap\PluginTestCase;

class TrackableModelTest extends PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        RequestLog::extend(function ($model) {
            $model->addDynamicProperty('trackableEvents', ['model.afterCreate']);
            $model->addDynamicProperty('trackableLogIpAddress', true);
            $model->addDynamicProperty('trackableLogUserAgent', true);
            $model->addDynamicProperty('trackableLogTrackChanges', true);
            $model->extendClassWith(\LukeTowers\EasyAudit\Behaviors\TrackableModel::class);
        });
    }

    public function testModelEventsAreTracked()
    {
        RequestLog::create([
            'url' => 'http://example.com',
            'status_code' => 200,
        ]);

        $query = Activity::where('subject_type', RequestLog::class);

        $this->assertEquals(true, $query->where('event', 'model.afterCreate')->exists());
    }

    public function testIpAddressIsTracked()
    {
        // @TODO: test local flag enabled / disabled
    }

    public function testUserAgentIsTracked()
    {
        // @TODO: test local flag enabled / disabled
    }

    public function testModelChangesAreTracked()
    {
        // TODO: Test model changes are tracked
        $this->assertTrue(true);
    }

    // @TODO: Finish implementing
    public function testEventsAreTrackedOnCurrentConnection()
    {
        $this->markTestSkipped("Not implemented yet");

        $record = new RequestLog(['url' => 'http://example.com', 'status_code' => 200]);
        $record->setConnection('connection_1');
        $record->save();

        // RequestLog::create([
        //     'url' => 'http://example.com',
        //     'status_code' => 200,
        // ]);

        $query = Activity::on('connection_1')
            ->where('subject_type', RequestLog::class);

        $this->assertEquals(true, $query->where('event', 'model.afterCreate')->exists());
    }
}
