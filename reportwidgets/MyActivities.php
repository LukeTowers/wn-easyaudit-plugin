<?php

namespace LukeTowers\EasyAudit\ReportWidgets;

use Backend\Classes\ReportWidgetBase;
use BackendAuth;
use LukeTowers\EasyAudit\Models\Activity;

class MyActivities extends ReportWidgetBase
{
    use \LukeTowers\EasyAudit\Traits\CanViewActivityRecord;

    const DEFAULT_RECORDS_COUNT = 10;
    const DEFAULT_TITLE = 'My Activities';

    /**
     * @var string The default alias to use for this widget
     */
    protected $defaultAlias = 'myActivities';

    /**
     * Get the upcoming events
     *
     * @return Collection
     */
    private function getData()
    {
        return Activity::fromSource(BackendAuth::getUser())
            ->orderBy('created_at', 'desc')
            ->limit($this->property("recordsCount", self::DEFAULT_RECORDS_COUNT))
            ->get();
    }

    /**
     * Renders the widget
     */
    public function render()
    {
        $this->vars['title'] = strtolower($this->property("title"));
        $this->vars['records'] = $this->getData();
        return $this->makePartial('widget');
    }

    /**
     * The properties should be defined in the defineProperties method of the widget class
     * Get the property data by $this->property("propertyName")
     *
     * @return array
     */
    public function defineProperties()
    {
        return [
            'title' => [
                'title'             => 'Widget title',
                'default'           => self::DEFAULT_TITLE,
                'type'              => 'string',
                'validationPattern' => '^.+$',
                'validationMessage' => 'The Widget Title is required.'
            ],

            'recordsCount' => [
                'title'             => 'Number of records to show (-1: ALL)',
                'default'           => self::DEFAULT_RECORDS_COUNT,
                'type'              => 'string',
                'validationPattern' => '^-?[0-9]+$'
            ],
        ];
    }

    /**
     * Load the assets
     *
     * @return void
     */
    protected function loadAssets()
    {
        parent::loadAssets();
        $this->addJs('/plugins/luketowers/easyaudit/assets/js/activityController.js');
    }
}
