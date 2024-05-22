<?php

namespace LukeTowers\EasyAudit\Classes;

use Backend\Facades\BackendAuth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use LukeTowers\EasyAudit\Models\Activity as ActivityModel;
use Winter\Storm\Exception\SystemException;

/**
 * Activity Logger service class to log activities (events) on models
 *
 * To use, create a new instance of this class and then either chain the methods for the data as required or call the `log()` method directly.
 * Example (all in one):
 *
 *    $activity = new ActivityLogger();
 *    $activity->log(
 *      'updated',
 *      'MyModel updated',
 *      $myModel,
 *      BackendAuth::getUser(),
 *      ['maintenanceMode' => true],
 *      'MyVendor.MyPlugin',
 *      'mysql'
 *   );
 *
 * Or (chained):
 *
 *    $activity = new ActivityLogger();
 *    $activity->onConnection('mysql')
 *              ->inLog('MyVendor.MyPlugin')
 *              ->for($myModel)
 *              ->by(BackendAuth::getUser())
 *              ->description('MyModel updated')
 *              ->properties(['maintenanceMode' => true])
 *              ->log('updated');
 *
 * @package luketowers/oc-easyaudit-plugin
 * @author Luke Towers
 */
class ActivityLogger
{
    use \LukeTowers\EasyAudit\Traits\EventHelper;

    const EVENT_PREFIX = 'luketowers.easyaudit.logger';

    //
    // Properties
    //

    /**
     * The name of the DB connection this event should be logged to
     */
    protected ?string $connection = null;

    /**
     * The name of the log this event belongs to
     */
    protected ?string $logName = null;

    /**
     * The name of the event
     */
    protected ?string $event = null;

    /**
     * The human-readable description of the event
     */
    protected ?string $description = null;

    /**
     * The subject (model the event is being performed on) of the event to be logged
     */
    protected ?Model $subject = null;

    /**
     * The source (model the event is being caused by) of the event to be logged
     */
    protected ?Model $source = null;

    /**
     * Additional properties to store with the log entry
     */
    protected array $properties = [];

    //
    // Internal Flags
    //

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
        // @TODO: Still doesn't prevent "duplicates" triggered by rapid succession of requests
        // i.e. user goes to update, accesses record, redirects to preview, accesses record - same timestamp will be issued.
        $cacheKey = 'luketowers.easyaudit.cachedRequestActivities';
        $this->bindEvent('afterLog', function () use ($cacheKey) {
            $activitiesLogged = Config::get($cacheKey, []);
            $activitiesLogged[] = $this->getHash();
            Config::set($cacheKey, $activitiesLogged);
        });
        $this->bindEvent('log', function () use ($cacheKey) {
            if (
                $this->activityCacheEnabled
                && in_array($this->getHash(), Config::get($cacheKey, []))
            ) {
                return true;
            }
        });

        $this->loggerPrepared = true;
    }

    /**
     * Log the event // NOTE: Probably horribly messy and inefficient, mvp it first
     *
     * @param string|array $event The name of the event to log, if this is an array then it
     * must be the only argument provided and it must be an array keyed by the other available parameters
     * @param string $description The human-readable description of the event to log
     * @param Model $subject The model the event is being performed on
     * @param Model $source The model the event is being performed by
     * @param array $properties The additional properties to store with the log entry
     * @param string $logName The log to log this activity to
     * @param string $connection The DB connection to log this activity in
     */
    public function log(
        string|array $event = '',
        string $description = '',
        Model $subject = null,
        Model $source = null,
        array $properties = [],
        string $logName = null,
        string $connection = null
    ) : void {
        // Prepare the logger
        $this->prepareLogger();

        if (is_array($event)) {
            extract($event);
        }

        // Populate the event information in this instance
        if (!empty($event)) {
            $this->event($event);
        }

        if (empty($this->event)) {
            throw new SystemException("The event name for an activity log entry cannot be empty.");
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

        if (!empty($connection)) {
            $this->onConnection($connection);
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

        // Default the source to the currently logged in backend user (if there is one)
        // ensuring that impersonators are logged as the true source of the activity
        if (is_null($this->source)) {
            $this->source = BackendAuth::getRealUser();
        }
        if ($this->validateModel($this->source)) {
            $activity->source = $this->source;
        }

        if (!empty($this->logName)) {
            $activity->log = $this->logName;
        }

        if ($this->connection) {
            $activity->setConnection($this->connection);
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
    public function validateModel($model): bool
    {
        return ($model instanceof Model && $model->exists);
    }

    /**
     * Clear this instance of it's data
     * NOTE: Probably only necessitated by the design choice to be able to chain method calls; tbd whether this stays in
     */
    public function clear(): static
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
        $this->properties = [];
        $this->logName = null;
        $this->connection = null;

        // Trigger an after clear event
        if ($originalSubject) {
            $originalSubject->fireEvent('activities.afterClear', [$this]);
        }

        return $this;
    }

    /**
     * Sets the event name for the event to be logged
     */
    public function event(string $event): static
    {
        $this->event = $event;
        return $this;
    }

    /**
     * Sets the description for the event to be logged
     */
    public function description(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Sets the subject of the event being logged
     */
    public function for(Model $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Sets the source of the event being logged
     */
    public function by(Model $source): static
    {
        $this->source = $source;
        return $this;
    }

    /**
     * Sets the additional properties to be stored with the event being logged
     */
    public function properties(array $properties): static
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * Sets the log name for the event to be logged
     */
    public function inLog(string $logName): static
    {
        $this->logName = $logName;
        return $this;
    }

    /**
     * Sets the name of the DB connection to log the event in
     */
    public function onConnection(string $connection): static
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * Check if the per-request de-duplication cache is enabled or change the state of it
     */
    public function requestActivityCache(bool $enable = null): bool
    {
        if ($enable !== null) {
            $this->activityCacheEnabled = $enable;
        }

        return $this->activityCacheEnabled;
    }

    /**
     * Calculate a hash key for the given logger instance
     */
    public function getHash(): string
    {
        $instance = [
            'event'         => $this->event,
            'description'   => $this->description,
            'subject_class' => $this->subject ? get_class($this->subject) : null,
            'subject_key'   => $this->subject ? $this->subject->getKey() : null,
            'source_class'  => $this->source ? get_class($this->source) : null,
            'source_key'    => $this->source ? $this->source->getKey() : null,
            'properties'    => $this->properties,
            'logName'       => $this->logName,
            'connection'    => $this->connection,
        ];

        return md5(json_encode($instance));
    }
}
