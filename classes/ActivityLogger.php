<?php namespace LukeTowers\ActivityLog\Classes;
    
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
 *    $activity->log('updated', 'MyModel updated', $myModel, BackendAuth::getUser(), ['maintenanceMode' => true]);
 * 
 * Or (chained):
 *
 *    $activity = new ActivityLogger();
 *    $activity->subject($myModel)->source(BackendAuth::getUser())->description('MyModel updated')->properties(['maintenanceMode' => true]);
 *    $activity->log('updated');
 *
 * @package luketowers/oc-activitylogger-plugin
 * @author Luke Towers
 */
class ActivityLogger
{
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
     * Log the event // NOTE: Probably horribly messy and inefficient, mvp it first
     *
     * @param string $event The name of the event to log
     * @param string $description The human-readable description of the event to log
     * @param Model $subject The model the event is being performed on
     * @param Model $source The model the event is being performed by
     * @param array $properties The additional properties to store with the log entry
     */
    public function log($event = '', $description = '', $subject = null, $source = null, $properties = array())
    {
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
        
        $activity->save();
        
        
        // Clear this instance to prevent leaks
        $this->clear();
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
}