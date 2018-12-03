<?php namespace LukeTowers\EasyAudit;

use Backend;
use System\Classes\PluginBase;

/**
 * EasyAudit Plugin Information File
 *
 * TODO:
 * - Activity type should perhaps be named activity action
 * - Add toggleable ability (default on) to log IP address of request that triggered the activity. Could log it in its own column, or under a _ip_address key in the properties column. If we add it as it's own column, then we wouldn't make it toggleable (but still make it nullable perhaps?) - Add UA for more detective work
 * - General Facade for generating activity entries
 * - Documentation
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
 * - Implement system dashboard for viewing all logs (in the settings -> logs section probably)
 * - Implement dashboard widget for tracking activity across the site (add filtering options)
 * - Implement Revisionable / trackable properties abilities
 *      Have an option as a model property as to what events to track the revisions on, and then in the backend, you would have the ability to restore revisions (as their own incremental change), but only on the events that revisions are tracked on
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
            'name'        => 'luketowers.easyaudit::lang.plugin.name',
            'description' => 'luketowers.easyaudit::lang.plugin.description',
            'author'      => 'LukeTowers',
            'icon'        => 'icon-list-alt',
            'homepage'    => 'https://github.com/LukeTowers/oc-easyaudit-plugin',
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
            'luketowers.easyaudit.manage_settings' => [
                'tab'   => 'luketowers.easyaudit::lang.plugin.name',
                'label' => 'luketowers.easyaudit::lang.permissions.manage_settings'
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
                'label'       => 'luketowers.easyaudit::lang.plugin.name',
                'description' => 'luketowers.easyaudit::lang.settings.description',
                'icon'        => 'icon-list-alt',
                'url'         => Backend::url('luketowers/easyaudit/settings'),
                'keywords'    => 'activity clear refresh log easyaudit audit',
                'permissions' => ['luketowers.easyaudit.manage_settings'],
            ],
        ];
    }
}
