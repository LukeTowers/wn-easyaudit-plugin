<?php

namespace LukeTowers\EasyAudit\Tests;

use Backend\Widgets\Form;
use LukeTowers\EasyAudit\Plugin;
use System\Tests\Bootstrap\PluginTestCase;
use Winter\Storm\Exception\SystemException;

class PluginTest extends PluginTestCase
{
    public function setUp(): void
    {
        $this->plugin = new Plugin($this->createApplication());
    }

    public function testPluginDetails()
    {
        $details = $this->plugin->pluginDetails();

        $this->assertIsArray($details);
        $this->assertArrayHasKey('name', $details);
        $this->assertArrayHasKey('description', $details);
        $this->assertArrayHasKey('icon', $details);
        $this->assertArrayHasKey('author', $details);

        $this->assertEquals('Luke Towers', $details['author']);
    }

    public function testRegisterPermissions()
    {
        $permissions = $this->plugin->registerPermissions();

        $this->assertIsArray($permissions);
    }

    /**
     * Resolve the activities FormWidget location for a form whose secondary tabs
     * outnumber its primary tabs, so the `true` heuristic and an explicit choice
     * resolve to different sections.
     */
    protected function resolveLocationFor($injectValue): ?string
    {
        $model = new \stdClass();
        $model->trackableInjectActivitiesFormWidget = $injectValue;

        $widget = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->getMock();
        $widget->model = $model;
        $widget->tabs = ['fields' => ['one' => [], 'two' => []]];
        $widget->secondaryTabs = ['fields' => ['a' => [], 'b' => [], 'c' => [], 'd' => []]];

        $method = new \ReflectionMethod($this->plugin, 'resolveActivitiesFormWidgetLocation');
        $method->setAccessible(true);

        return $method->invoke($this->plugin, $widget);
    }

    public function testActivitiesWidgetIsNotInjectedWhenDisabled()
    {
        $this->assertNull($this->resolveLocationFor(false));
    }

    public function testActivitiesWidgetDefaultsToTheLargestTabSection()
    {
        // secondaryTabs holds 4 fields against the primary tabs' 2.
        $this->assertEquals('secondaryTabs', $this->resolveLocationFor(true));
    }

    public function testActivitiesWidgetHonoursAnExplicitLocation()
    {
        foreach (Plugin::ACTIVITIES_FORM_WIDGET_LOCATIONS as $location) {
            $this->assertEquals($location, $this->resolveLocationFor($location));
        }

        // The explicit choice must win over the size heuristic, which would
        // otherwise have picked secondaryTabs for this form.
        $this->assertEquals('tabs', $this->resolveLocationFor('tabs'));
    }

    public function testActivitiesWidgetRejectsAnUnknownLocation()
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionMessage('Invalid $trackableInjectActivitiesFormWidget value');

        $this->resolveLocationFor('sidebar');
    }
}
