<?php

namespace LukeTowers\EasyAudit\Behaviors;

use BackendAuth;
use Config;
use LukeTowers\EasyAudit\Classes\ActivityLogger;
use LukeTowers\EasyAudit\Models\Activity;
use Winter\Storm\Database\ModelBehavior as ModelBehaviorBase;

/**
 * Trackable model extension
 *
 * Usage:
 *
 * In the model class definition:
 *
 *   public $implement = ['@LukeTowers.EasyAudit.Behaviors.TrackableModel'];
 *
 *   /**
 *    * @var bool Flag to allow identical activities being logged on the same request. Defaults to false
 *    * /
 *   protected $trackableAllowDuplicates = true;
 *
 *   /**
 *    * @var array The model events that are to be tracked as activities ['event', 'event'] || ['event' => [name => 'name', description => 'description']]
 *    * /
 *   public $trackableEvents = ['model.afterSave', 'model.afterCreate', 'model.afterDelete'];
 *
 *   /**
 *    * @var array The custom event names to override the default event names within the activity entry
 *    * NOTE: if not otherwise specified, only the 'after' version of the desired event will be listened to
 *    * /
 *   public $trackableEventNames = ['model.beforeCreate' => 'creation_started', 'model.afterCreate' => 'creation_completed', 'model.afterUpdate' => 'updated'];
 *
 *   /**
 *    * @var array The custom event descriptions to override the default event descriptions within the activity entry
 *    * /
 *   public $trackableEventDescriptions = ['model.beforeCreate' => 'The model creation process has been started', 'model.afterCreate' => 'The model was created'];
 *
 *   /**
 *    * @var bool Manually control the IP address logging on this model (default from the luketowers.easyaudit.logIpAddress config setting)
 *    * /
 *   public $trackableLogIpAddress = true
 *
 *   /**
 *    * @var bool Manually control the User agent logging on this model (default from the luketowers.easyaudit.logUserAgent config setting)
 *    * /
 *   public $trackableLogUserAgent = true
 *
 *   /**
 *    * @var bool Manually control the change tracking on this model (default from the luketowers.easyaudit.trackChanges config setting)
 *    * /
 *   public $trackableTrackChanges = true
 *
 *   /**
 *    * @var array List of attributes to ignore when tracking changes on this model. Note that if an update event is triggered and there
 *    * are no changes to the tracked attributes on the model, no activity will be logged.
 *    * /
 *   public $trackableIgnoredAttributes = []
 *
 *   /**
 *    * @var bool Manually control if the activities field gets automatically injected into backend forms
 *    * for this model (default from the luketowers.easyaudit.autoInjectActvitiesFormWidget config setting)
 *    * /
 *   public $trackableInjectActvitiesFormWidget = true
 *
 * @package luketowers/oc-easyaudit-plugin
 * @author Luke Towers
 */
class TrackableModel extends ModelBehaviorBase
{
    /**
     * @var ActivityLogger Instance of an ActivityLogger to use
     */
    protected $logger;

    /**
     * @var bool Flag for the logger being initialized yet or not
     */
    protected $loggerPopulated;

    public function __construct($model)
    {
        parent::__construct($model);

        // Load the model property that was initialized by the parent behavior
        $model = $this->model;

        // Setup the inverse of the polymorphic ActivityModel relationship to this model
        $model->morphMany['activities'] = [Activity::class, 'name' => 'subject'];

        // Instantiate the logger, setting the subject to this model
        $this->logger = new ActivityLogger();

        // Prevent duplicate activities from being logged on the same request
        if (empty($model->trackableAllowDuplicates)) {
            $this->logger->requestActivityCache(true);
        }

        // Setup the default events to be tracked
        if (!$model->propertyExists('trackableEvents')) {
            $model->addDynamicProperty('trackableEvents', [
                'model.afterCreate' => [
                    'name' => 'created',
                    'description' => 'luketowers.easyaudit::lang.models.activity.trackableEvents.model.afterCreate',
                ],
                'model.afterUpdate' => [
                    'name' => 'updated',
                    'description' => 'luketowers.easyaudit::lang.models.activity.trackableEvents.model.afterUpdate',
                ],
                'model.afterDelete' => [
                    'name' => 'archived',
                    'description' => 'luketowers.easyaudit::lang.models.activity.trackableEvents.model.afterDelete',
                ],
            ]);
        }

        // Ensure that the logger is setup for every event it can handle
        if (!empty($model->trackableEvents)) {
            $callable = function () {
                if ($this->model->exists) {
                    $this->populateLogger();
                }
            };
            foreach ($model->trackableEvents as $event => $config) {
                if (is_string($config)) {
                    $event = $config;
                }
                $model->bindEvent($event, $callable, 9999);
            }
        }

        $this->populateLogger();

        // Refresh the populated data of the logger after it gets cleared
        $model->bindEvent('activities.clear', function () {
            $this->loggerPopulated = false;
        });
        $model->bindEvent('activities.afterClear', function ($logger) {
            $this->logger = $logger;
            $this->populateLogger();
        });

        // Setup the event tracking if desired by the model implementing this behavior
        $this->setupEventTracking($model);
    }

    /**
     * Populate the logger object with the necessary data
     */
    protected function populateLogger()
    {
        // Don't bother initializing more than once
        if ($this->loggerPopulated) {
            return;
        }

        // Default the subject to the loaded model
        $this->logger->for($this->model);

        // Default the logName to the plugin code of the loaded model
        // TODO: Document ability to control the log name from the model
        if ($this->model->methodExists('trackableGetLogName')) {
            $this->logger->inLog($this->model->trackableGetLogName($this->model));
        } else {
            $this->logger->inLog($this->trackableGetLogName($this->model));
        }

        // Default the source to the currently logged in backend user (if there is one)
        // ensuring that impersonators are logged as the true source of the activity
        $user = BackendAuth::getRealUser();
        if ($user) {
            $this->logger->by($user);
        }

        $this->loggerPopulated = true;
    }

    /**
     * Get the log name to use from the namespace of the model being tracked
     *
     * @param Model $model
     * @return string $logName
     */
    public function trackableGetLogName($model)
    {
        $logName = 'default';
        $namespaced = explode('\\', get_class($model));

        if (count($namespaced) === 1) {
            $logName = $namespaced[0];
        } elseif (count($namespaced) === 3 && in_array($namespaced[0], Config::get('cms.loadModules', ['Backend', 'Cms', 'System']))) {
            $logName = 'Module.' . $namespaced[0];
        } else {
            $logName = $namespaced[0] . '.' . $namespaced[1];
        }

        return $logName;
    }

    /**
     * Setup the event tracking on the provided model
     *
     * @var Model $model The model to setup the tracking on
     */
    protected function setupEventTracking($model)
    {
        if (empty($model->trackableEvents) && !is_array($model->trackableEvents)) {
            return;
        }

        // Create the callback function that will be triggered by every tracked event
        $callable = function () use ($model) {
            // Don't bother going any further if the logger hasn't been populated yet
            if (!$this->loggerPopulated) {
                return;
            }

            // Attempt to get the event that was triggered
            // NOTE: Super dirty, pretty reliant on internal Winter implementation of fireEvent() method
            $eventName = null;
            $backtrace = debug_backtrace(false, 3);
            if (!empty($backtrace[2]['args'][0])) {
                $eventName = $backtrace[2]['args'][0];
            }

            // NOTE: may want to support custom model events, which would also include passing the custom
            // event's arguments to this method as well)
            $this->triggerModelActivity($model, $eventName);
        };

        // Loop through the events to track
        foreach ($model->trackableEvents as $event => $config) {
            if (is_string($config)) {
                $event = $config;
            }

            $model->bindEvent($event, $callable);
        }
    }

    /**
     * Handle logging a model event as an activity
     *
     * @param Model $model The model that the event has been triggered on
     * @param string $event The name of the event that has been triggered
     */
    protected function triggerModelActivity($model, $eventName = null)
    {
        // Initialize the default information for the activity to be logged
        $activityName = 'modelEventFired';
        $activityDescription = 'A model event was fired';

        // Apply any custom event names / descriptions for the event that was triggered
        if (!empty($eventName)) {
            if (!empty($model->trackableEventNames[$eventName])) {
                $activityName = $model->trackableEventNames[$eventName];
            } elseif (!empty($model->trackableEvents[$eventName]['name'])) {
                $activityName = $model->trackableEvents[$eventName]['name'];
            } else {
                $activityName = $eventName;
            }

            if (!empty($model->trackableEventDescriptions[$eventName])) {
                $activityDescription = $model->trackableEventDescriptions[$eventName];
            } elseif (!empty($model->trackableEvents[$eventName]['description'])) {
                $activityDescription = $model->trackableEvents[$eventName]['description'];
            } else {
                $activityDescription = "The $eventName internal event was fired";
            }
        }

        $this->activity($activityName)->description($activityDescription)->log();
    }

    /**
     * Return the logger instance to create a log entry
     *
     * @param string $event The name of the event to log - optional, but must be specified at some point before the log is saved
     */
    public function activity($event = '')
    {
        if (!$this->loggerPopulated) {
            throw new \Exception("The model being tracked hasn't been properly loaded yet and thus the logger is not initialized");
        }

        if (!empty($event)) {
            $this->logger->event($event);
        }

        return $this->logger;
    }
}
