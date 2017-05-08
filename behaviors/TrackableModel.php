<?php namespace LukeTowers\ActivityLog\Behaviors;

use BackendAuth;
use October\Rain\Database\ModelBehavior as ModelBehaviorBase;

use LukeTowers\ActivityLog\Classes\ActivityLogger;

/**
 * Trackable model extension
 *
 * Usage:
 *
 * In the model class definition:
 *
 *   public $implement = ['@LukeTowers.ActivityLog.Behaviors.TrackableModel'];
 *
 *   /**
 *    * @var array The model events that are to be tracked as activities
 *    * /
 *   public $trackableEvents = ['save', 'create' => ['before' => true, 'after' => false]];
 *
 *   /**
 *    * @var array The custom event names to override the default event names within the activity entry
 *    * NOTE: if not otherwise specified, only the 'after' version of the desired event will be listened to
 *    * /
 *   public $trackableEventNames = ['beforeCreate' => 'creation_started', 'afterCreate' => 'creation_completed', 'afterUpdate' => 'updated'];
 *
 *   /**
 *    * @var array The custom event descriptions to override the default event descriptions within the activity entry
 *    * /
 *   public $trackableEventDescriptions = ['beforeCreate' => 'The model creation process has been started', 'afterCreate' => 'The model was created'];
 *
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
        $model->morphMany['activities'] = ['LukeTowers\ActivityLog\Models\Activity', 'name' => 'subject'];
        
        // Instantiate the logger, setting the subject to this model
        $this->logger = new ActivityLogger();
        
        // Populate the logger after loading the necessary data
        $model->bindEvent('model.afterFetch', function () use ($model) {
            $this->populateLogger($this->logger);
        }, 9999);
        
        // Refresh the populated data of the logger after it gets cleared
        $model->bindEvent('activities.clear', function () {
            $this->loggerPopulated = false;
        });
        $model->bindEvent('activities.afterClear', function ($logger) {
            $this->populateLogger($logger);
        });
        
        // Setup the event tracking if desired by the model implementing this behavior
        $this->setupEventTracking($model);
    }
    
    /**
     * Populate the logger object with the necessary data
     *
     * @param ActivityLogger $logger The logger object to populate
     */
    protected function populateLogger($logger)
    {
        // Default the subject to the loaded model
        $logger->for($this->model);
    
        // Default the source to the currently logged in backend user (if there is one)
        $user = BackendAuth::getUser();
        if ($user) {
            $logger->by($user);
        }
        
        $this->loggerPopulated = true;
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
            // NOTE: Super dirty, pretty reliant on internal October implementation of fireEvent() method
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
        foreach ($model->trackableEvents as $key => $value) {
            // Populate the name of the targeted event
            $event = [];
            if (is_string($value)) {
                $event['name'] = $value;
            } else {
                $event['name'] = $key;
            }
            
            // Fill in the default options for this event
            $event = array_merge($event, [
                'before' => false,
                'after'  => true,
            ]);
            
            // Listen to the before version of the event
            if ($event['before']) {
                $model->bindEvent('model.before' . ucfirst($event['name']), $callable);
            }
            
            // Listen to the after version of the event
            if ($event['after']) {
                $model->bindEvent('model.after' . ucfirst($event['name']), $callable);
            }
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
            } else {
                $activityName = $eventName;
            }
            
            if (!empty($model->trackableEventDescriptions[$eventName])) {
                $activityDescription = $model->trackableEventDescriptions[$eventName];
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