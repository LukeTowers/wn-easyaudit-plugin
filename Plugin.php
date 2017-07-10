<?php namespace LukeTowers\ActivityLog;

use Backend;
use System\Classes\PluginBase;

/**
 * ActivityLog Plugin Information File
 *
 * TODO:
 * - Implement log name (the idea is to be used to filter logs by the plugin that generates them)
 * - Implement system dashboard for viewing all logs (in the settings -> logs section probably)
 * - Implement dashboard widget for tracking activity across the site (add filtering options)
 * - Activity type should perhaps be named activity action
 * - Add toggleable ability (default on) to log IP address of request that triggered the activity. Could log it in its own column, or under a _ip_address key in the properties column. If we add it as it's own column, then we wouldn't make it toggleable (but still make it nullable perhaps?)
 * - Documentation
 * - General Facade for generating activity entries
 * - Implement Revisionable / trackable properties abilities
 *      Have an option as a model property as to what events to track the revisions on, and then in the backend, you would have the ability to restore revisions (as their own incremental change), but only on the events that revisions are tracked on
 * - Implement templateable descriptions through supporting language strings, some way to provide the attributes and their assigned keys
 *      for being used in the language string as variables. ($activity->description(':event was triggered by :source.full_name'))
 *      To be considered: Import / export of log entries when using language strings. Reevaluate pros/cons of using translateable strings
 *      for the event description in the first place. Perhaps use a system allowing admins to replace event name / description labels for
 *      other users.
 * - Implement configurable rolling log system to dump activity db contents (perhaps even create export jobs for different activity queries),
 *      that will remove db entries past a set date and export them into an importable format and compress them on the disk. Could be part of
 *      a "pro" release
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'luketowers.activitylog::lang.plugin.name',
            'description' => 'luketowers.activitylog::lang.plugin.description',
            'author'      => 'LukeTowers',
            'icon'        => 'icon-list-alt',
            'homepage'    => 'https://github.com/LukeTowers/oc-activitylog-plugin',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'luketowers.activitylog.manage_settings' => [
                'tab'   => 'luketowers.activitylog::lang.plugin.name',
                'label' => 'luketowers.activitylog::lang.permissions.manage_settings'
            ],
        ];
    }

    /**
	 * Registers the settings used by this plugin
	 *
	 * @return array
	 */
    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'luketowers.activitylog::lang.plugin.name',
                'description' => 'luketowers.activitylog::lang.settings.description',
                'icon'        => 'icon-list-alt',
                'url'         => Backend::url('luketowers/activitylog/settings'),
                'keywords'    => 'activity clear refresh log activitylog',
                'permissions' => ['luketowers.activitylog.manage_settings'],
            ],
        ];
    }
}