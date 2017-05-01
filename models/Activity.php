<?php namespace LukeTowers\ActivityLog\Models;

use Model;

/**
 * Activity Model
 */
class Activity extends Model
{
    use \October\Rain\Database\Traits\SoftDelete;
    
    /**
     * @var string The database table used by the model
     */
    public $table = 'luketowers_activitylog_activities';

    /**
     * @var array The attributes to protect against mass-assignment
     */
    public $guarded = [];

    /**
     * @var array The attributes to type cast on set and get
     */
    protected $casts = [
        'properties' => 'collection',
    ];

    /**
     * Get the owning subject model
     */
    public function subject()
    {
        if (config('laravel-activitylog.subject_returns_soft_deleted_models')) {
            return $this->morphTo()->withTrashed();
        }

        return $this->morphTo();
    }

    /**
     * Get the owning causer model
     */
    public function causer()
    {
        return $this->morphTo();
    }

    /**
     * Get the properties with the given name.
     *
     * @param string $propertyName
     * @return mixed
     */
    public function getExtraProperty(string $propertyName)
    {
        return array_get($this->properties->toArray(), $propertyName);
    }

    /**
     * Get the changes to the subject model
     *
     * @return array $changes ['attributes' => ['attribute' => 'value'], 'old' => ['attribute' => 'value']]
     */
    public function getChangesAttribute()
    {
        return collect(array_filter($this->properties->toArray(), function ($key) {
            return in_array($key, ['attributes', 'old']);
        }, ARRAY_FILTER_USE_KEY));
    }

    /**
     * Scope a query to only include activities in the provided log names
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $logNames Array or String of log names to search for activities in
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInLog($query, ...$logNames)
    {
        if (is_array($logNames[0])) {
            $logNames = $logNames[0];
        }

        return $query->whereIn('log_name', $logNames);
    }

    /**
     * Scope a query to only include activities by a given causer.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $causer
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCausedBy($query, $causer)
    {
        return $query
            ->where('causer_type', get_class($causer))
            ->where('causer_id', $causer->getKey());
    }

    /**
     * Scope a query to only include activities for a given subject.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model $subject
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForSubject($query, $subject)
    {
        return $query
            ->where('subject_type', get_class($subject))
            ->where('subject_id', $subject->getKey());
    }
}
