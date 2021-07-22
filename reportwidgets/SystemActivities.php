<?php namespace LukeTowers\EasyAudit\ReportWidgets;

use Backend\Classes\ReportWidgetBase;
use LukeTowers\EasyAudit\Models\Activity;

class SystemActivities extends ReportWidgetBase
{
    use \LukeTowers\EasyAudit\Traits\CanViewActivityRecord;

    const DEFAULT_RECORDS_COUNT = 10;
    const DEFAULT_TITLE = 'System Activities';

    /**
     * @var string The default alias to use for this widget
     */
    protected $defaultAlias = 'systemActivities';

    /**
     * Get the upcoming events
     *
     * @return Collection
     */
    private function getData()
    {
        $query = Activity::orderBy('created_at', 'desc')
            ->limit($this->property("recordsCount", self::DEFAULT_RECORDS_COUNT));

        if ($source = $this->property('source')) {
            $source = explode('|', $source);
            $source = $source[1]::find($source[0]);
            if ($source) {
                $query->fromSource($source);
            }
        }

        if ($subject = $this->property('subject')) {
            $subject = explode('|', $subject);
            $subject = $subject[1]::find($subject[0]);
            if ($subject) {
                $query->forSubject($subject);
            }
        }

        return $query->get();
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

            'source'       => [
                'title'   => 'Source',
                'default' => null,
                'type'    => 'dropdown',
                'placeholder' => 'Select...',
            ],

            // TODO: This should be implemented as a recordfinder type popup to watch a single
            // specific record
            'subject'      => [
                'title'   => 'Subject',
                'default' => null,
                'type'    => 'dropdown',
                'placeholder' => 'Select...',
            ],
        ];
    }

    public function getSourceOptions()
    {
        return (new Activity)->getSourceOptions();
    }

    public function getSubjectOptions()
    {
        return (new Activity)->getSubjectOptions(null, null, 10);
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
