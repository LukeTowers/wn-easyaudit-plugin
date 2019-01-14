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
     * Relations
     */
    public $morphTo = [
        'subject' => [],
        'source'  => [],
    ];

    /**
     * Scope a query to only include activities with the provided event name descriptor
     *
     * @param Builder $query
     * @param mixed $logNames Array or String of event names to search for activities in
     * @return Builder
     */
    public function scopeWithEvent($query, ...$eventNames)
    {
        if (is_array($eventNames[0])) {
            $eventNames = $eventNames[0];
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
     * @param Model $source
     * @return Builder
     */
    public function scopeFromSource($query, $source)
    {
        return $query
            ->where('source_type', get_class($source))
            ->where('source_id', $source->getKey());
    }

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
            return $this->source->full_name;
        } else {
            return Lang::get('luketowers.easyaudit::lang.models.activity.unknown_source');
        }
    }

    /**
     * Filter
     */
    private function getDistinctColumnOptions($columnName)
    {
        $records = Activity::distinct($columnName)->select($columnName)->get();
        $result = [];
        foreach ($records as $record) {
            $result[$record->{$columnName}] = $record->{$columnName};
        }
        return $result;
    }
    public function getLogOptions()
    {
        return $this->getDistinctColumnOptions('log');
    }
    public function scopeFromLog($query, $log)
    {
        return $query->whereIn('log',$log);
    }

    public function getEventOptions()
    {
        return $this->getDistinctColumnOptions('event');
    }

    /**
     *
     *
     * @param array $availableSources  [ ['source_id'=>1, 'source_type'=> 'Backend\Models\User'] ]
     * @return array The Key is model class, value is Ids ['Backend\Models\User'=> [1,2,3], ... ]
     */
    protected function getUserIdsInTables($availableSources)
    {
        $userTables = [];
        foreach ($availableSources as $source) {
            $sourceClass = $source['source_type'];
            if (array_key_exists($sourceClass, $userTables)) {
                $userTables[$sourceClass][] = $source['source_id'];
            } else {
                $userTables[$sourceClass] = [$source['source_id']];
            }
        }
        return $userTables;
    }

    public function getUserOptions()
    {
        $dbSources = static::select('source_id', 'source_type')->distinct('source_id', 'source_type')->get();
        $availableSources = [];
        foreach ($dbSources as $source) {
            $availableSources[] = ['source_id' => $source->source_id,
                                'source_type'=>$source->source_type];
        }

        $userTables = $this->getUserIdsInTables($availableSources);
        $result = [];
        foreach ($userTables as $sourceClass => $userIds) {
            $records = $sourceClass::whereIn('id', $userIds)->select('id','first_name', 'last_name')->get();
            foreach ($records as $record) {
                $result[$record->id . '|' . get_class($record)] = $record->first_name . ' ' . $record->last_name;
            }
        }
        return $result;
    }

    /**
     * Fitler with the users
     *
     * @param QueryBuilder $query
     * @param array $filterSources ['id|classname','']
     * @return void
     */
    public function scopeWithUsers($query, $filterSources)
    {
        $availableSources = [];
        foreach ($filterSources as $filterSource) {
            $source = explode('|', $filterSource);
            $availableSources[] = [
                'source_id' => $source[0],
                'source_type' => $source[1]
            ];
        }
        $userTables = $this->getUserIdsInTables($availableSources);
        // where source_type = '' and source_id in []
        // orWhere source_type = '' and source_id in []
        $firstLine = true;
        foreach ($userTables as $class => $userIds) {
            if ($firstLine){
                $query->where(function ($q) use ($class, $userIds) {
                    $q->where('source_type', $class)->whereIn('source_id', $userIds);
                });
            }else{
                $query->orWhere(function ($q) use ($class, $userIds) {
                    $q->where('source_type', $class)->whereIn('source_id', $userIds);
                });
            }
            $firstLine = false;
        }
        return $query;
    }


}
