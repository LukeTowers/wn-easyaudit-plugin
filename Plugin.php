<?php

namespace LukeTowers\EasyAudit;

use Backend\Facades\Backend;
use Backend\Models\User;
use LukeTowers\EasyAudit\Behaviors\TrackableModel;
use LukeTowers\EasyAudit\FormWidgets\ActivityLog;
use LukeTowers\EasyAudit\FormWidgets\ChangeViewer;
use LukeTowers\EasyAudit\Models\Activity;
use System\Classes\PluginBase;
use System\Classes\PluginManager;
use Winter\Storm\Exception\SystemException;
use Winter\Storm\Support\Facades\Config;
use Winter\Storm\Support\Facades\Event;

/**
 * EasyAudit Plugin Information File
 *
 * TODO:
 * - General Facade for generating activity entries
 * - Documentation
 * - Add trackableGetRecordName and trackableGetRecordUrl methods for the activity log to detect the name and URL for a given activity target
 * - Implement templateable descriptions through supporting language strings, some way to provide the attributes and their assigned keys
 *      for being used in the language string as variables. ($activity->description(':event was triggered by :source.full_name'))
 *      To be considered: Import / export of log entries when using language strings. Reevaluate pros/cons of using translateable strings
 *      for the event description in the first place. Perhaps use a system allowing admins to replace event name / description labels for
 *      other users.
 *
 * TODO: Paid version (SystemAuditer or something like that)
 * - Implement ability to enable this plugin's features on other third party plugins that don't actually have support for this plugin built in
 *      Could be very useful, especially the revisionable trait, and even just in general the ability to listen to other plugins and configure auditing for them automatically
 * - Implement ability to have configurable drivers for ouput of the tracking capabilities
 * - Implement Revisionable / trackable properties abilities
 *      Have an option as a model property as to what events to track the revisions on, and then in the backend, you would have the ability to restore revisions (as their own incremental change), but only on the events that revisions are tracked on
 * - Implement configurable rolling log system to dump activity db contents (perhaps even create export jobs for different activity queries),
 *      that will remove db entries past a set date and export them into an importable format and compress them on the disk. Could be part of
 *      a "pro" release
 */
class Plugin extends PluginBase
{
    /**
     * @var array Form sections the activities FormWidget can be explicitly injected into
     */
    const ACTIVITIES_FORM_WIDGET_LOCATIONS = ['fields', 'tabs', 'secondaryTabs'];

    /**
     * @var bool Plugin requires elevated permissions in order to continue logging changes
     * in privileged areas of the application
     */
    public $elevated = true;

    /**
     * Returns information about this plugin.
     */
    public function pluginDetails(): array
    {
        return [
            'name'        => 'luketowers.easyaudit::lang.plugin.name',
            'description' => 'luketowers.easyaudit::lang.plugin.description',
            'author'      => 'Luke Towers',
            'icon'        => 'icon-list-alt',
            'homepage'    => 'https://github.com/LukeTowers/wn-easyaudit-plugin',
        ];
    }

    /**
     * Registers any backend permissions used by this plugin.
     */
    public function registerPermissions(): array
    {
        return [
            'luketowers.easyaudit.clear_logs' => [
                'tab'   => 'luketowers.easyaudit::lang.plugin.name',
                'label' => 'luketowers.easyaudit::lang.permissions.clear_logs'
            ],
            'luketowers.easyaudit.activities.view_all' => [
                'tab'   => 'luketowers.easyaudit::lang.plugin.name',
                'label' => 'luketowers.easyaudit::lang.permissions.activities.view_all'
            ],
            'luketowers.easyaudit.activities.view_own' => [
                'tab'   => 'luketowers.easyaudit::lang.plugin.name',
                'label' => 'luketowers.easyaudit::lang.permissions.activities.view_own'
            ],
        ];
    }

    /**
     * Registers the settings used by this plugin
     */
    public function registerSettings(): array
    {
        return [
            'logs' => [
                'label' => 'luketowers.easyaudit::lang.controllers.activities.label',
                'description' => 'luketowers.easyaudit::lang.controllers.activities.description',
                'icon' => 'icon-eye',
                'url' => Backend::url('luketowers/easyaudit/activities'),
                'order' => 1100,
                'permissions' => [
                    'luketowers.easyaudit.activities.*',
                ],
                'category' => \System\Classes\SettingsManager::CATEGORY_LOGS,
            ],
        ];
    }

    /**
     * Register the plugin's form widgets
     */
    public function registerFormWidgets(): array
    {
        return [
            ActivityLog::class => 'activitylog',
            ChangeViewer::class => 'changeviewer',
        ];
    }

    /**
     * Register the plugin's report widgets
     */
    public function registerReportWidgets(): array
    {
        return [
            \LukeTowers\EasyAudit\ReportWidgets\MyActivities::class => [
                'label'       => 'luketowers.easyaudit::lang.widgets.myactivities.label',
                'context'     => 'dashboard',
                'permissions' => [
                    'luketowers.easyaudit.activities.view_own'
                ],
            ],
            \LukeTowers\EasyAudit\ReportWidgets\SystemActivities::class => [
                'label'       => 'luketowers.easyaudit::lang.widgets.systemactivities.label',
                'context'     => 'dashboard',
                'permissions' => [
                    'luketowers.easyaudit.activities.view_all'
                ],
            ],
        ];
    }

    /**
     * Runs when the plugin is booted
     */
    public function boot(): void
    {
        if (PluginManager::$noInit && $this->app->runningInConsole()) {
            return;
        }
        $this->registerMediaLibraryTracking();
        $this->registerModelTracking();
        $this->extendBackendForms();
        $this->extendBackendUserModel();
    }

    /**
     * Setup the media library tracking
     */
    protected function registerMediaLibraryTracking(): void
    {
        Event::listen('media.*', function ($eventName, $params) {
            $action = '';
            switch ($eventName) {
                case 'media.file.delete':
                case 'media.folder.delete':
                    $action = 'deleted';
                    break;
                case 'media.file.move':
                case 'media.folder.move':
                    $action = 'moved';
                    break;
                case 'media.file.rename':
                case 'media.folder.rename':
                    $action = 'renamed';
                    break;
                case 'media.file.upload':
                case 'media.file.streamedUpload':
                    $action = 'uploaded';
                    break;
                case 'media.folder.create':
                    $action = 'created';
                    break;
            }

            $properties = [];
            if (count($params) === 3 && is_string($params[2])) {
                $description = $params[1] . " was $action to " . $params[2];
                $properties = [
                    'changes' => [
                        'path' => [
                            'from' => $params[1],
                            'to' => $params[2],
                        ],
                    ],
                ];
            } else {
                $description = $params[1] . " was $action";
                $properties = ['path' => $params[1]];
            }

            audit()
                ->inLog('System.Media')
                ->event($eventName)
                ->description($description)
                ->properties($properties)
                ->log();
        });
    }

    /**
     * Setup the model tracking
     */
    protected function registerModelTracking(): void
    {
        $modelsToTrack = Config::get('luketowers.easyaudit::modelsToTrack', []);
        foreach ($modelsToTrack as $class => $config) {
            if (is_array($config)) {
                $modelClass = $class;
            } else {
                $modelClass = $config;
                $config = [];
            }

            if (!class_exists($modelClass)) {
                continue;
            }

            $modelClass::extend(function ($model) use ($config) {
                // Only inject when the config actually specifies it. An
                // unconditional call adds an empty dynamic property, and
                // dynamic properties take precedence over real ones in
                // ExtendableTrait::extendableGet(), so a model that declares
                // its own $trackableIgnoredAttributes would have it shadowed
                // purely by being listed in modelsToTrack.
                if (array_key_exists('ignoredAttributes', $config)) {
                    $model->addDynamicProperty('trackableIgnoredAttributes', $config['ignoredAttributes']);
                }
                $model->extendClassWith(TrackableModel::class);
            });
        }
    }

    /**
     * Extend the backend forms to add the activity log widget to models
     * implementing the TrackableModel behavior
     */
    protected function extendBackendForms(): void
    {
        if ($this->app->runningInBackend()) {
            // Add the audit log to models implementing trackable model
            Event::listen('backend.form.extendFieldsBefore', function (\Backend\Widgets\Form $widget) {
                if (
                    $widget->isNested
                    || (
                        method_exists($widget->model, 'isClassExtendedWith')
                        && !$widget->model->isClassExtendedWith(TrackableModel::class)
                    )
                ) {
                    return;
                }

                $location = $this->resolveActivitiesFormWidgetLocation($widget);
                if (empty($location)) {
                    return;
                }

                $widget->{$location}['fields'] = array_merge($widget->{$location}['fields'] ?? [], [
                    'activities' => [
                        'tab' => 'luketowers.easyaudit::lang.models.activity.audit_log',
                        'context' => ['update', 'preview', 'relation'],
                        'type' => 'activitylog',
                        'span' => 'full',
                        'cssClass' => 'container'
                    ],
                ]);

                // The outside fields aren't tabbed, so there's no tab to put an icon on.
                if ($location !== 'fields') {
                    $widget->{$location}['icons']['luketowers.easyaudit::lang.models.activity.audit_log'] = 'icon-eye';
                }
            });
        }
    }

    /**
     * Resolve which section of the provided form the activities FormWidget should be
     * injected into, from the model's $trackableInjectActivitiesFormWidget property
     * (falling back to the luketowers.easyaudit.autoInjectActivitiesFormWidget config
     * setting). Accepted values:
     *
     *   false            Don't inject the widget
     *   true             Inject it into whichever tab section already holds the most fields
     *   'fields'         Inject it into the form's outside fields
     *   'tabs'           Inject it into the form's primary tabs
     *   'secondaryTabs'  Inject it into the form's secondary tabs
     *
     * @return string|null The form section to inject into, or null to skip injection
     */
    protected function resolveActivitiesFormWidgetLocation(\Backend\Widgets\Form $widget): ?string
    {
        $location = $widget->model->trackableInjectActivitiesFormWidget
            ?? Config::get('luketowers.easyaudit.autoInjectActivitiesFormWidget', true);

        // Default to whichever section is already carrying the bulk of the form.
        if ($location === true) {
            return count($widget->tabs['fields'] ?? []) > count($widget->secondaryTabs['fields'] ?? [])
                ? 'tabs'
                : 'secondaryTabs';
        }

        if (in_array($location, static::ACTIVITIES_FORM_WIDGET_LOCATIONS, true)) {
            return $location;
        }

        if (!empty($location)) {
            throw new SystemException(sprintf(
                'Invalid $trackableInjectActivitiesFormWidget value (%s) on %s. Expected true, false, or one of: %s',
                var_export($location, true),
                get_class($widget->model),
                "'" . implode("', '", static::ACTIVITIES_FORM_WIDGET_LOCATIONS) . "'"
            ));
        }

        return null;
    }

    /**
     * Extend the backend user model to add the $user->name accessor for
     * use in the activity log
     */
    protected function extendBackendUserModel(): void
    {
        User::extend(function ($model) {
            if (empty($model->name)) {
                $model->addDynamicMethod('getNameAttribute', function () use ($model) {
                    return $model->first_name . ' ' . $model->last_name;
                });
            }

            // Setup the inverse of the polymorphic ActivityModel relationship to this model
            $model->addMorphManyRelation('user_activities', [Activity::class, 'name' => 'source']);

            // Hide activities from the array version of the model
            $model->addHidden(['user_activities']);
        });

        // Add the audit log to models implementing trackable model
        Event::listen('backend.form.extendFieldsBefore', function (\Backend\Widgets\Form $widget) {
            if (
                $widget->isNested
                || !($widget->model instanceof User)
                || !($widget->getController() instanceof \Backend\Controllers\Users)
            ) {
                return;
            }

            $tabsFields = $widget->tabs['fields'] ?? [];
            $secondaryTabsFields = $widget->secondaryTabs['fields'] ?? [];
            $location = (count($tabsFields) > count($secondaryTabsFields)) ? 'tabs' : 'secondaryTabs';

            if ($widget->context === 'myaccount') {
                $widget->{$location}['fields'] = array_merge(${$location . 'Fields'}, [
                    // Only users with view_all permissions should be able to see the audit log
                    // for their own account as it could reveal sensitive information about the
                    // system administrators
                    'activities@myaccount' => [
                        'tab' => 'luketowers.easyaudit::lang.models.activity.audit_log',
                        'type' => 'activitylog',
                        'permissions' => ['luketowers.easyaudit.activities.view_all'],
                        'span' => 'full',
                        'cssClass' => 'container'
                    ],
                    'own_activities' => [
                        'tab' => 'luketowers.easyaudit::lang.models.activity.label_plural',
                        'context' => ['myaccount'],
                        'type' => 'activitylog',
                        'permissions' => ['luketowers.easyaudit.activities.view_own'],
                        'subject' => false,
                        'source' => 'formModel',
                        'span' => 'full',
                        'cssClass' => 'container'
                    ],
                ]);
            } else {
                $widget->{$location}['fields'] = array_merge(${$location . 'Fields'}, [
                    '_user_activities' => [
                        'tab' => 'luketowers.easyaudit::lang.models.activity.label_plural',
                        'context' => ['update', 'preview'],
                        'permissions' => ['luketowers.easyaudit.activities.view_all'],
                        'type' => 'activitylog',
                        'subject' => false,
                        'source' => 'formModel',
                        'span' => 'full',
                        'cssClass' => 'container'
                    ],
                ]);
            }

            $widget->{$location}['icons']['luketowers.easyaudit::lang.models.activity.label_plural'] = 'icon-hourglass';
        });
    }
}
