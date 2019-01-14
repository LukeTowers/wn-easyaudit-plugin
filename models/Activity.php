<?php namespace LukeTowers\EasyAudit\Models;

use Lang;
use Model;

/**
 * Activity Model
 */
class Activity extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model
     */
    public $table = 'luketowers_easyaudit_activities';

    /**
     * @var array Validation rules
     */
    public $rules = [
        'event' => 'required|between:1,255',
    ];

    /**
     * @var array The attributes to protect against mass-assignment
     */
    public $guarded = ['created_at'];

    /**
     * @var array Attribute names to encode and decode using JSON.
     */
    protected $jsonable = ['properties'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at'];
    const CREATED_AT = 'created_at';

    /**
     * Disable setting the updated_at column automatically as this model doesn't support that column
     *
     * @param mixed $value
     * @return $this
     */
    public function setUpdatedAt($value)
    {
        return $this;
    }

    /**
     * Relations
     */
    public $morphTo = [
        'subject' => [],
        'source'  => [],
    ];

    /**
     * Filter activities in the provided logs
     *
     * @param QueryBuilder $query
     * @param string|array $log
     * @return void
     */
    public function scopeInLog($query, $log)
    {
        if (!is_array($log)) {
            $log = [$log];
        }

        return $query->whereIn('log', $log);
    }

    /**
     * Filter activities with the provided event name descriptors
     *
     * @param Builder $query
     * @param mixed $logNames array or string of event names to search for activities in
     * @return Builder
     */
    public function scopeWithEvent($query, ...$eventNames)
    {
        if (is_array($eventNames[0])) {
            $eventNames = $eventNames[0];
        } elseif (count($eventNames) === 1) {
            $eventNames = [$eventNames];
        }

        return $query->whereIn('event', $eventNames);
    }

    /**
     * Scope a query to only include activities for a given subject.
     *
     * @param Builder $query
     * @param Model $subject
     * @return Builder
     */
    public function scopeForSubject($query, $subject)
    {
        return $query
            ->where('subject_type', get_class($subject))
            ->where('subject_id', $subject->getKey());
    }

    /**
     * Scope a query to only include activities by a given source.
     *
     * @param Builder $query
     * @param Model $source The source to filter by
     * @return Builder
     */
    public function scopeFromSource($query, $source)
    {
        return $query->where('source_id', $source->getKey())->where('source_type', get_class($source));
    }

    /**
     * Scope a query to only include activities by a given source.
     *
     * @param Builder $query
     * @param array $sources The sources to filter by, in the form of ['id|type']
     * @return Builder
     */
    public function scopeFromSources($query, ...$sources)
    {
        if (is_array($sources[0])) {
            $sources = $sources[0];
        } elseif (count($sources) === 1) {
            $sources = [$sources];
        }

        $sourcesByType = [];
        foreach ($sources as $source) {
            $source = explode('|', $source);
            $sourcesByType[$source[1]][] = $source[0];
        }

        if (!empty($sourcesByType)) {
            $first = true;
            foreach ($sourcesByType as $type => $ids) {
                if ($first) {
                    $query->where(function ($q) use ($type, $ids) {
                        $q->where('source_type', $type)->whereIn('source_id', $ids);
                    });
                    $first = false;
                } else {
                    $query->orWhere(function ($q) use ($type, $ids) {
                        $q->where('source_type', $type)->whereIn('source_id', $ids);
                    });
                }
            }
        }

        return $query;
    }

    /**
     * Get only the unique values for the provided column
     *
     * @param string $column
     * @return void
     */
    protected function getUniquesFromColumn($column)
    {
        $records = static::select($column)->distinct($column)->get();

        $result = [];
        foreach ($records as $record) {
            $result[$record->{$column}] = $record->{$column};
        }
        return $result;
    }

    /**
     * Get the available logs
     *
     * @return array ['log' => 'log']
     */
    public function getLogOptions()
    {
        $logs = $this->getUniquesFromColumn('log');
        return array_combine($logs, $logs);
    }

    /**
     * Get the available events
     *
     * @return array ['event' => 'event']
     */
    public function getEventOptions()
    {
        $events = $this->getUniquesFromColumn('event');
        return array_combine($events, $events);
    }

    /**
     * Get the available sources
     *
     * @return array $result ['id|type' => $activty->source_name]
     */
    public function getSourceOptions()
    {
        $result = [];
        $distinctSources = static::distinct('source_id', 'source_type')->get();

        foreach ($distinctSources as $activity) {
            $result[$activity->source_id . '|' . $activity->source_type] = $activity->source_name;
        }

        return $result;
    }


    /**
     * Get the source name
     *
     * @return string Name of the source for this activity item
     */
    public function getSourceNameAttribute($value)
    {
        if (!empty($value)) {
            return $value;
        }

        if ($this->source) {
            return basename(str_replace('\\', '/', get_class($this->source))) . ': ' . $this->source->name;
        } else {
            return Lang::get('luketowers.easyaudit::lang.models.activity.unknown_source');
        }
    }

    /**
     * Get the target name
     *
     * @return string Name of the target of this activity item
     */
    public function getTargetNameAttribute($value)
    {
        if (!empty($value)) {
            return $value;
        }

        if ($this->target) {
            return basename(str_replace('\\', '/', get_class($this->target))) . ': ' . $this->target->name;
        } else {
            return Lang::get('luketowers.easyaudit::lang.models.activity.unknown_source');
        }
    }
}
