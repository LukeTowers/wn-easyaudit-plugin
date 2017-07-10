<?php namespace LukeTowers\ActivityLog\Classes;

use Config;
use ApplicationException;
use Illuminate\Database\Eloquent\Model;

use LukeTowers\ActivityLog\Models\Activity as ActivityModel;

/**
 * Activity Logger service class to log activities (events) on models
 *
 * To use, create a new instance of this class and then either chain the methods for the data as required or call the `log()` method directly.
 * Example (all in one):
 *
 *    $activity = new ActivityLogger();
 *    $activity->log('updated', 'MyModel updated', $myModel, BackendAuth::getUser(), ['maintenanceMode' => true], 'MyVendor.MyPlugin');
 *
 * Or (chained):
 *
 *    $activity = new ActivityLogger();
 *    $activity->inLog('MyVendor.MyPlugin')
 *              ->for($myModel)
 *              ->by(BackendAuth::getUser())
 *              ->description('MyModel updated')
 *              ->properties(['maintenanceMode' => true])
 *              ->log('updated');
 *
 * @package luketowers/oc-activitylogger-plugin
 * @author Luke Towers
 */
class ActivityLogger
{
    use \LukeTowers\ActivityLog\Traits\EventHelper;

    const EVENT_PREFIX = 'luketowers.activitylog.logger';

    /**
     * @var string The name of the event
     */
    protected $event;

    /**
     * @var string The human-readable description of the event
     */
    protected $description;

    /**
     * @var Model The subject (model the event is being performed on) of the event to be logged
     */
    protected $subject;

    /**
     * @var Model The source (model the event is being caused by) of the event to be logged
     */
    protected $source;

    /**
     * @var array Additional properties to store with the log entry
     */
    protected $properties;

    /**
     * @var string The name of the log this event belongs to
     */
    protected $logName;

    /**
     * @var bool Flag for whether this logger instance has been prepared for logging
     */
    protected $loggerPrepared = false;

    /**
     * @var bool Store request de-duplication cache state.
     */
    protected $activityCacheEnabled = false;

    /**
     * Prepare for saving an activity event
     */
    protected function prepareLogger()
    {
        if ($this->loggerPrepared) {
            return;
        }

        // Deduplicate activities on the same request cycle
        if ($this->activityCacheEnabled) {
            $cacheKey = 'luketowers.activitylog.cachedRequestActivities';
            $this->bindEvent('afterLog', function () use ($cacheKey) {
                $activitiesLogged = Config::get($cacheKey, []);
                $activitiesLogged[] = $this->getHash();
                Config::set($cacheKey, $activitiesLogged);
            });
            $this->bindEvent('log', function () use ($cacheKey) {
                if (in_array($this->getHash(), Config::get($cacheKey, []))) {
                    return true;
                }
            });
        }

        $this->loggerPrepared = true;
    }

    /**
     * Log the event // NOTE: Probably horribly messy and inefficient, mvp it first
     *
     * @param string $event The name of the event to log
     * @param string $description The human-readable description of the event to log
     * @param Model $subject The model the event is being performed on
     * @param Model $source The model the event is being performed by
     * @param array $properties The additional properties to store with the log entry
     * @param string $logName The log to log this activity to
     */
    public function log($event = '', $description = '', $subject = null, $source = null, $properties = array(), $logName = null)
    {
        // Prepare the logger
        $this->prepareLogger();

        // Populate the event information in this instance
        if (!empty($event)) {
            $this->event($event);
        }

        if (empty($this->event)) {
            throw new ApplicationException("The event name for an activity log entry cannot be empty.");
        }

        if (!empty($description)) {
            $this->description($description);
        }

        if ($this->validateModel($subject)) {
            $this->for($subject);
        }

        if ($this->validateModel($source)) {
            $this->by($source);
        }

        if (!empty($properties)) {
            $this->properties($properties);
        }

        if (!empty($logName)) {
            $this->inLog($logName);
        }


        // Create and populate the activity entry to be stored in the database
        $activity = new ActivityModel([
            'event' => $this->event,
            'description' => $this->description,
            'properties'  => $this->properties,
        ]);

        if ($this->validateModel($this->subject)) {
            $activity->subject = $this->subject;
        }

        if ($this->validateModel($this->source)) {
            $activity->source = $this->source;
        }

        if (!empty($this->logName)) {
            $activity->log = $this->logName;
        }

        // Provide opportunity to prevent this activity from being logged
        // NOTE: If prevented from logging, this logger instance will be left 'dirty'
        if (!$this->fireCombinedEvent('log', [$activity], true)) {
            // Log the activity
            $activity->save();

            // Extensibility:
            $this->fireCombinedEvent('afterLog', [$activity], false);

            // Clear this instance to prevent leaks
            $this->clear();
        }
    }

    /**
     * Validate that the model provided is in fact a Model object and that it exists
     *
     * @param mixed $model The variable to check
     * @return bool The model's validatity (true = valid, false = invalid)
     */
    protected function validateModel($model)
    {
        return ($model instanceof Model && $model->exists);
    }

    /**
     * Clear this instance of it's data
     * NOTE: Probably only necessitated by the design choice to be able to chain method calls; tbd whether this stays in
     *
     * @return ActivityLogger $this
     */
    public function clear()
    {
        // Trigger a before clear event
        $originalSubject = null;
        if ($this->validateModel($this->subject)) {
            $originalSubject = $this->subject;
            $originalSubject->fireEvent('activities.clear', [$this]);
        }

        // Clear the properties
        $this->event = null;
        $this->description = null;
        $this->subject = null;
        $this->source = null;
        $this->properties = null;
        $this->logName = null;

        // Trigger an after clear event
        if ($originalSubject) {
            $originalSubject->fireEvent('activities.afterClear', [$this]);
        }

        return $this;
    }

    /**
     * Sets the event name for the event to be logged
     *
     * @param string $event The name of the event being logged
     * @return ActivityLogger $this
     */
    public function event(string $event)
    {
        $this->event = $event;
        return $this;
    }

    /**
     * Sets the description for the event to be logged
     *
     * @param string $description The human-readable description of the event being logged
     * @return ActivityLogger $this
     */
    public function description(string $description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Sets the subject of the event being logged
     *
     * @param Model The subject (model the event is being performed on) of the event to be logged
     * @return ActivityLogger $this
     */
    public function for(Model $subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Sets the source of the event being logged
     *
     * @param Model The source (model the event is being caused by) of the event to be logged
     * @return ActivityLogger $this
     */
    public function by(Model $source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * Sets the description for the event to be logged
     *
     * @param array Additional properties to store with the log entry
     * @return ActivityLogger $this
     */
    public function properties(array $properties)
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * Sets the log name for the event to be logged
     *
     * @param string $logName The log to log this activity to
     * @return ActivityLogger $this
     */
    public function inLog(string $logName)
    {
        $this->logName = $logName;
        return $this;
    }

    /**
     * Check if the per-request de-duplication cache is enabled or change the state of it
     *
     * @param bool $switch Enable or disable the per-request de-duplication cache
     * @return bool
     */
    public function requestActivityCache($switch = null)
    {
        if ($switch !== null) {
            $this->activityCacheEnabled = $switch;
        }

        return $this->activityCacheEnabled;
    }

    /**
     * Calculate a hash key for the given logger instance
     *
     * @return string
     */
    public function getHash()
    {
        $instance = [
            'event'         => $this->event,
            'description'   => $this->description,
            'subject_class' => get_class($this->subject),
            'subject_key'   => @$this->subject->getKey(),
            'source_class'  => get_class($this->source),
            'source_key'    => @$this->source->getKey(),
            'properties'    => $this->properties,
            'logName'       => $this->logName,
        ];

        return md5(serialize($instance));
    }
}