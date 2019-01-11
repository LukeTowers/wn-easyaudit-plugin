<?php return [
    'plugin' => [
        'name'        => 'EasyAudit',
        'description' => 'View and manage audit logs for models within your project',
    ],

    'models' => [
        'activity' => [
            'label'          => 'Activity',
            'label_plural'   => 'Activities',
            'event'          => 'Type',
            'description'    => 'Description',
            'subject'        => 'Subject',
            'source'         => 'Source',
            'source_user'    => 'User',
            'created_at'     => 'Activity Date',
            'unknown_source' => 'Unknown source',
        ],
    ],

    'permissions' => [
        'manage_settings' => 'Manage EasyAudit Settings',
    ],

    'settings'    => [
        'description'       => 'Manage EasyAudit Settings',
        'empty_log'         => 'Empty System-Wide Audit Log',
        'empty_log_confirm' => 'Are you sure you want to empty the audit logs system-wide? This is not reversible!',
        'empty_log_success' => 'The system-wide audit logs have been emptied',
    ],
    'activities' => [
        'description' => 'View all activities',
    ],
];
