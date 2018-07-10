<?php return [
    'plugin' => [
        'name'        => 'Activity Log',
        'description' => 'Manage and view activity logs for models within your project',
    ],

    'models' => [
        'activity' => [
            'label'          => 'Activity',
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
        'manage_settings' => 'Manage Activity Log Settings',
    ],

    'settings'    => [
        'description'       => 'Manage Activity Log Settings',
        'empty_log'         => 'Empty System-Wide Activity Log',
        'empty_log_confirm' => 'Are you sure you want to empty the activity logs system-wide? This is not reversible!',
        'empty_log_success' => 'The system-wide activity logs have been emptied',
    ],
];
