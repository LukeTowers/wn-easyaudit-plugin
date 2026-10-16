<?php

namespace LukeTowers\EasyAudit\Tests;

use Illuminate\Database\Eloquent\Relations\Relation;
use LukeTowers\EasyAudit\Models\Activity;
use System\Models\RequestLog;
use System\Tests\Bootstrap\PluginTestCase;

class TrackableModelTest extends PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // The logging flags are deliberately not registered here. They would
        // only restate the config defaults, and addDynamicProperty() ignores a
        // key that is already present, so setting them centrally would stop
        // individual tests from exercising the per-model overrides.
        RequestLog::extend(function ($model) {
            $model->addDynamicProperty('trackableEvents', ['model.afterCreate', 'model.afterUpdate']);
            $model->extendClassWith(\LukeTowers\EasyAudit\Behaviors\TrackableModel::class);
        });
    }

    public function tearDown(): void
    {
        Relation::morphMap([], false);

        parent::tearDown();
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
        $record = RequestLog::create(['url' => 'http://example.com', 'status_code' => 200]);

        $record->status_code = 404;
        $record->save();

        $activity = Activity::where('subject_type', RequestLog::class)
            ->where('event', 'model.afterUpdate')
            ->orderBy('id', 'desc')
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayHasKey('status_code', $activity->properties['changes'] ?? []);
    }

    /**
     * A `trackableIgnoredAttributes` list registered with addDynamicProperty()
     * must be honoured.
     *
     * This is how Plugin::registerModelTracking() applies the modelsToTrack
     * config to models that do not declare the behaviour themselves, and it
     * previously had no effect at all: Activity read the property with
     * `?? []`, and `??` consults __isset(), which does not see dynamic
     * properties, so the ignore list always resolved to empty.
     */
    public function testDynamicallyRegisteredIgnoredAttributesAreHonoured()
    {
        RequestLog::extend(function ($model) {
            $model->addDynamicProperty('trackableIgnoredAttributes', ['status_code']);
        });

        $record = RequestLog::create(['url' => 'http://example.com', 'status_code' => 200]);

        $record->status_code = 404;
        $record->url = 'http://example.com/changed';
        $record->save();

        $activity = Activity::where('subject_type', RequestLog::class)
            ->where('event', 'model.afterUpdate')
            ->orderBy('id', 'desc')
            ->first();

        $this->assertNotNull($activity);

        $changes = $activity->properties['changes'] ?? [];
        $this->assertArrayNotHasKey('status_code', $changes, 'Ignored attribute was recorded.');
        $this->assertArrayHasKey('url', $changes, 'Non-ignored attribute was not recorded.');
    }

    /**
     * The same __isset() blind spot silently disabled every other per-model
     * override when it was registered dynamically rather than declared on the
     * class, because the null-coalesce fell through to the config default.
     */
    public function testDynamicallyRegisteredLoggingFlagsAreHonoured()
    {
        RequestLog::extend(function ($model) {
            $model->addDynamicProperty('trackableLogIpAddress', false);
            $model->addDynamicProperty('trackableLogUserAgent', false);
        });

        RequestLog::create(['url' => 'http://example.com', 'status_code' => 200]);

        $activity = Activity::where('subject_type', RequestLog::class)
            ->where('event', 'model.afterCreate')
            ->orderBy('id', 'desc')
            ->first();

        $this->assertNotNull($activity);
        $this->assertNull($activity->ip_address, 'IP address was logged despite the model opting out.');
        $this->assertArrayNotHasKey('user_agent', $activity->properties ?? []);
    }

    /**
     * The morph relations store a model's morph map alias as its subject_type,
     * so the lookup has to match on the alias rather than on the class name.
     */
    public function testActivitiesStoredUnderAMorphAliasAreFoundForTheSubject()
    {
        Relation::morphMap(['request_log' => RequestLog::class]);

        $record = RequestLog::create(['url' => 'http://example.com', 'status_code' => 200]);

        $this->assertSame(['request_log'], Activity::forSubject($record)->pluck('subject_type')->all());
    }

    /**
     * Rows logged before the app registered the alias still hold the class name.
     */
    public function testActivitiesStoredBeforeTheMorphAliasAreStillFoundForTheSubject()
    {
        $record = RequestLog::create(['url' => 'http://example.com', 'status_code' => 200]);

        Relation::morphMap(['request_log' => RequestLog::class]);

        $record->status_code = 404;
        $record->save();

        $this->assertEqualsCanonicalizing(
            [RequestLog::class, 'request_log'],
            Activity::forSubject($record)->pluck('subject_type')->all()
        );
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
