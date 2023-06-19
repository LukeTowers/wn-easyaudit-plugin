<?php

namespace LukeTowers\EasyAudit\Models;

use Backend;
use Config;
use Lang;
use Model;
use Winter\Storm\Support\Str;

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
            $changes = $this->getSubjectChanges();
            if (empty($changes)) {
                return false;
            } else {
                $this->properties = array_merge($this->properties ?? [], ['changes' => $changes]);
            }
        }
    }

    /**
     * Check to see if the IP address can be logged
     */
    public function canLogIpAddress(): bool
    {
        return (bool) (
            $this->subject?->trackableLogIpAddress
            ?? Config::get('luketowers.easyaudit::logIpAddress', true)
        );
    }

    /**
     * Check to see if the user agent can be logged
     */
    public function canLogUserAgent(): bool
    {
        return (bool) (
            $this->subject?->trackableLogUserAgent
            ?? Config::get('luketowers.easyaudit::logUserAgent', true)
        );
    }

    /**
     * Check to see if the changed model attributes can be tracked
     */
    public function canTrackChanges(): bool
    {
        // Can't track changes if there is no subject
        if (!$this->subject) {
            return false;
        }

        return (bool) (
            $this->subject->trackableTrackChanges
            ?? Config::get('luketowers.easyaudit::trackChanges', true)
        );
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
        $options = [];
        $subjectTypes = static::distinct('subject_type')->lists('subject_type');

        foreach ($subjectTypes as $class) {
            if (empty($class)) {
                continue;
            }
            $parts = explode('\\', $class);
            if (count($parts) === 3) {
                $options[$class] = $parts[0] . ' ' . $parts[2];
            } elseif (count($parts) === 4) {
                // This is a plugin
                $pluginCode = $parts[0] . '.' . $parts[1];
                $plugin = \System\Classes\PluginManager::instance()->findByIdentifier($pluginCode);
                if ($plugin) {
                    $pluginCode = Lang::get($plugin->pluginDetails()['name'] ?? $pluginCode);
                }
                $options[$class] = $pluginCode . ': ' . $parts[3];
            }
        }

        return $options;
    }

    /**
     * Accessor for $activity->source_url attribute
     */
    public function getSourceUrlAttribute($value): ?string
    {
        if (!empty($value)) {
            return $value;
        }

        if ($this->source) {
            return $this->guessUrlForModel($this->source);
        }
    }

    /**
     * Accessor for $activity->subject_url attribute
     */
    public function getSubjectUrlAttribute($value): ?string
    {
        if (!empty($value)) {
            return $value;
        }

        if ($this->subject) {
            return $this->guessUrlForModel($this->subject);
        }
    }

    /**
     * Guess the backend preview URL for a model
     * @TODO: add support for attributes on the subject model or the model passed itself
     * for manually setting the URL
     */
    protected function guessUrlForModel(Model $model): ?string
    {
        $namespace = explode('\\', get_class($model));
        $class = array_pop($namespace);

        // Drop the last part of the namespace
        array_pop($namespace);

        // Generate the guessed namespace of the controller
        $controllerClass = implode('\\', $namespace) . '\\Controllers\\' . Str::plural($class);

        if (class_exists($controllerClass)) {
            $controllerAction = 'preview';

            $reflector = new \ReflectionClass($controllerClass);
            $parts = pathinfo($reflector->getFileName());
            $viewFolder = $parts['dirname'] . DIRECTORY_SEPARATOR . Str::lower($parts['filename'] . DIRECTORY_SEPARATOR);

            $actions = ['preview', 'update'];
            foreach ($actions as $action) {
                if (file_exists($viewFolder . $action . '.php') || file_exists($viewFolder . $action . '.htm')) {
                    $controllerAction = $action;
                    break;
                }
            }

            // Generate the guessed URL to the controller
            return Backend::url(Str::lower(
                implode('/', $namespace) . '/' .
                Str::plural($class) . "/$controllerAction/" . $model->getKey()
            ));
        }

        return null;
    }

    /**
     * Get the sources's name for human consumption
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

        // Return a nice subject for media.* events
        if ($this->log === 'System.Media') {
            return $this->properties['path'] ?? $this->properties['changes']['path']['from'] ?? '';
        }

        if (is_null($this->subject)) {
            return '';
        }

        $prefix = basename(str_replace('\\', '/', $this->subject_type)) . ': ';
        if ($this->subject) {
            $subjectKey = $this->subject->name ?: $this->subject->title ?: $this->subject->getKey();
        } else {
            $subjectKey = $this->subject_id;
        }

        return $prefix . $subjectKey;
    }

    /**
     * Accessor for $activity->description
     */
    public function getDescriptionAttribute($value): string
    {
        if (empty($value)) {
            $value = '';
        }

        // @TODO: Support using attributes from the event and the subject's / source's attributes
        // array processed by array_dot inside of the localized message template
        return Lang::get($value);
    }

    /**
     * Get the changes that have ocurred to the subject
     */
    public function getSubjectChanges(): array
    {
        if (empty($this->subject)) {
            return [];
        }

        $ignoredAttributes = array_merge(
            [
                'updated_at',
            ],
            $this->subject?->trackableIgnoredAttributes ?? []
        );

        $changes = [];
        foreach ($this->subject->getChanges() as $key => $change) {
            if (in_array($key, $ignoredAttributes)) {
                continue;
            }

            $from = $this->subject->getOriginal($key);
            $to = $change;

            // Ignore null => '' changes
            if (is_null($from) && $to === '') {
                continue;
            }

            $changes[$key] = [
                'from' => $from,
                'to' => $to,
            ];
        }

        return $changes;
    }
}
