<?php namespace LukeTowers\EasyAudit\Traits;

use LukeTowers\EasyAudit\Models\Activity;

trait CanViewActivityRecord
{
    //
    // Configurable properties
    //

    /**
     * @var array Form field configuration
     */
    public $form = '$/luketowers/easyaudit/models/activity/fields.yaml';

    //
    // Internal properties
    //

    /**
     * @var Backend\Widgets\Form Reference to the form widget object.
     */
    protected $activityRecordWidget;

    /**
     * Get the Form widget used for the activity record popup
     *
     * @return Backend\Widgets\Form The intialized Form widget
     */
    protected function getActivityRecordWidget()
    {
        if ($this->activityRecordWidget) {
            return $this->activityRecordWidget;
        }

        // Configure the Form widget
        $config = $this->makeConfig($this->form);
        $config->model = $this->getCurrentActivity();
        $alias = !empty($this->alias) ? $this->alias : basename(str_replace('\\', '/', get_class($this)));
        $config->arrayName = $alias . 'Form';
        $config->isNested = true;

        // Initialize the Form widget
        $widget = $this->makeWidget('Backend\Widgets\Form', $config);
        $widget->previewMode = true;
        $widget->bindToController();

        return $this->activityRecordWidget = $widget;
    }

    /**
     * Get the currently active activity record
     *
     * @return Activity
     */
    protected function getCurrentActivity()
    {
        $activity = Activity::find(post('recordId'));

        if (!$activity) {
            $activity = new Activity;
        }

        return $activity;
    }

    /**
     * AJAX handler to view a specific activity item's details
     *
     * @return string
     */
    public function onViewLogItemDetails()
    {
        return $this->makePartial('$/luketowers/easyaudit/partials/popup.activitydetails.htm', ['form' => $this->getActivityRecordWidget()], false);
    }
}