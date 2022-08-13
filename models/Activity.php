<?php namespace LukeTowers\EasyAudit\Models;

use Model;
use Config;

/**
 * Activity Model
 */
class Activity extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

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
     * Process the activity entry before saving it
     *
     * @return void
     */
    public function beforeSave()
    {
        $request = request();
        if ($this->canLogIpAddress()) {
            $this->ip_address = $request->ip();
        }

        if ($this->canLogUserAgent()) {
            $this->properties = array_merge($this->properties ?? [], ['user_agent' => $request->header('User-Agent')]);
        }

        if ($this->canTrackChanges()) {
            // @TODO: Implement this
        }
    }

    /**
     * Check to see if the IP address can be logged
     *
     * @return bool
     */
    public function canLogIpAddress()
    {
        $can = false;
        if (empty($this->subject->trackableDisableIpLogging)) {
            $can = Config::get('luketowers.easyaudit.logIpAddress', true);
        } else {
            $can = $this->subject->trackableDisableIpLogging;
        }

        return (bool) $can;
    }

    /**
     * Check to see if the user agent can be logged
     *
     * @return bool
     */
    public function canLogUserAgent()
    {
        $can = false;
        if (empty($this->subject->trackableDisableUserAgentLogging)) {
            $can = Config::get('luketowers.easyaudit.logUserAgent', true);
        } else {
            $can = $this->subject->trackableDisableUserAgentLogging;
        }

        return (bool) $can;
    }

    /**
     * Check to see if the changed model attributes can be tracked
     *
     * @return bool
     */
    public function canTrackChanges()
    {
        $can = false;
        if (empty($this->subject->trackableDisableChangeTracking)) {
            $can = Config::get('luketowers.easyaudit.changeTracking', true);
        } else {
            $can = $this->subject->trackableDisableChangeTracking;
        }

        return (bool) $can;
    }

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
        return $query
            ->where('source_type', get_class($source))
            ->where('source_id', $source->getKey());
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
     * Scope a query to only include activities to a given subject type.
     *
     * @param Builder $query
     * @param array $subjectTypes The subject_types to filter by, in the form of ['type']
     * @return Builder
     */
    public function scopeToSubjectTypes($query, ...$subjectTypes)
    {
        if (is_array($subjectTypes[0])) {
            $subjectTypes = $subjectTypes[0];
        } elseif (count($subjectTypes) === 1) {
            $subjectTypes = [$subjectTypes];
        }

        return $query->whereIn('subject_type', $subjectTypes);
    }

    /**
     * Get only the unique values for the provided column
     *
     * @param string $column
     * @param Model|null The subject that we're filtering options for
     * @param Model|null The source that we're filtering options for
     * @return array
     */
    protected function getUniquesFromColumn($column, $subject = null, $source = null)
    {
        $query = static::select($column)->distinct($column);
        if ($subject) {
            $query->addSelect('subject_type', 'subject_id')->forSubject($subject);
        }
        if ($source) {
            $query->addSelect('source_type', 'source_id')->fromSource($source);
        }

        $records = $query->get();

        $result = [];
        foreach ($records as $record) {
            $result[$record->{$column}] = $record->{$column};
        }
        return $result;
    }

    /**
     * Get the available logs
     *
     * @param Model|null The subject that we're filtering options for
     * @param Model|null The source that we're filtering options for
     * @return array ['log' => 'log']
     */
    public function getLogOptions($subject = null, $source = null)
    {
        $logs = $this->getUniquesFromColumn('log', $subject, $source);
        return array_combine($logs, $logs);
    }

    /**
     * Get the available events
     *
     * @param Model|null The subject that we're filtering options for
     * @param Model|null The source that we're filtering options for
     * @return array ['event' => 'event']
     */
    public function getEventOptions($subject = null, $source = null)
    {
        $events = $this->getUniquesFromColumn('event', $subject, $source);
        return array_combine($events, $events);
    }

    /**
     * Get the available sources
     *
     * @param Model|null The subject that we're filtering options for
     * @param Model|null The source that we're filtering options for
     * @return array $result ['id|type' => $activity->source_name]
     */
    public function getSourceOptions($subject = null, $source = null, $limit = 500)
    {
        $result = [];
        if ($source) {
            $result[$source->id . '|' . get_class($source)] = basename(str_replace('\\', '/', get_class($source))) . ': ' . $source->name;
        } else {
            $query = static::distinct('source_id', 'source_type');

            if ($subject) {
                $query->forSubject($subject);
            }

            $distinctSources = $query->limit($limit)->get();

            foreach ($distinctSources as $activity) {
                $result[$activity->source_id . '|' . $activity->source_type] = $activity->source_name;
            }
        }

        return $result;
    }

    /**
     * Get the available subjects
     *
     * @param Model|null The subject that we're filtering options for
     * @param Model|null The source that we're filtering options for
     * @return array $result ['id|type' => $activity->source_name]
     */
    public function getSubjectOptions($subject = null, $source = null, $limit = 500)
    {
        $result = [];
        if ($subject) {
            $result[$subject->id . '|' . get_class($subject)] = basename(str_replace('\\', '/', get_class($subject))) . ': ' . $subject->name;
        } else {
            $query = static::distinct('subject_id', 'subject_type');

            if ($source) {
                $query->fromSource($source);
            }

            $distinctSubjects = $query->limit($limit)->get();

            foreach ($distinctSubjects as $activity) {
                $result[$activity->subject_id . '|' . $activity->subject_type] = $activity->subject_name;
            }
        }

        return $result;
    }

    /**
     * Get the available subject types
     *
     * @param Model|null The subject that we're filtering options for
     * @param Model|null The source that we're filtering options for
     * @return array $result ['type' => $activity->source_name]
     */
    public function getSubjectTypeOptions($subject = null, $source = null, $limit = 500)
    {
        // @TODO: Trim prefix off of options
        return static::distinct('subject_type')->lists('subject_type', 'subject_type');
    }

    /**
     * Get the souces's name for human consumption
     */
    public function getSourceNameAttribute($value): string
    {
        if (!empty($value)) {
            return $value;
        }

        $prefix = basename(str_replace('\\', '/', $this->source_type)) . ': ';
        if ($this->source) {
            $sourceKey = $this->source->name ?: $this->source->title ?: $this->source->getKey();
        } else {
            $sourceKey = $this->source_key;
        }

        return $prefix . $sourceKey;
    }

    /**
     * Get the subject's name for human consumption
     */
    public function getSubjectNameAttribute($value): string
    {
        if (!empty($value)) {
            return $value;
        }

        $prefix = basename(str_replace('\\', '/', $this->subject_type)) . ': ';
        if ($this->subject) {
            $subjectKey = $this->subject->name ?: $this->subject->title ?: $this->subject->getKey();
        } else {
            $subjectKey = $this->subject_id;
        }

        return $prefix . $subjectKey;
    }
}
