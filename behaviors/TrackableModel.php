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
 */
class TrackableModel extends ModelBehaviorBase
{
    /**
     * @var ActivityLogger Instance of an ActivityLogger to use
     */
    protected $logger;
    
    public function __construct($model)
    {
        parent::__construct($model);
        
        // Setup the inverse of the polymorphic ActivityModel relationship to this model
        $this->model->morphMany = array_merge($this->model->morphMany, [
            'activities' => ['LukeTowers\ActivityLog\Models\Activity' => 'subject'],
        ]);
        
        // Instantiate the logger, setting the subject to this model
        $this->logger = new ActivityLogger();
        $this->logger->for($this->model);
        
        // Default the source to the currently logged in backend user (if there is one)
        $user = BackendAuth::getUser();
        if ($user) {
            $this->logger->by($user);
        }
    }
    
    /**
     * Return the logger instance to create a log entry
     *
     * @param string $event The name of the event to log - optional, but must be specified at some point before the log is saved
     */
    public function activity($event = '')
    {
        if (!empty($event)) {
            $this->logger->event($event);
        }
        
        return $this->logger;
    }
}