<?php

return [
    'plugin' => [
        'name'        => 'EasyAudit',
        'description' => 'View and manage audit logs for models within your project',
    ],

    'models' => [
        'activity' => [
            'label'        => 'Activity',
            'label_plural' => 'Activities',
            'audit_log'    => 'Audit Log',
            'log'          => 'Log',
            'event'        => 'Action',
            'description'  => 'Description',
            'subject'      => 'Subject',
            'source'       => 'Source',
            'source_user'  => 'User',
            'created_at'   => 'Activity Date',
            'ip_address'   => 'IP Address',
            'url'          => 'URL',
            'changes'      => 'Changes',
            'no_changes'   => 'No changes captured by event',
            'change_from'  => 'From',
            'change_to'    => 'To',
            'user_agent'   => 'User Agent',
            'tab_request'  => 'Request',
            'unknown'      => 'Unknown',
            'trackableEvents' => [
                'model' => [
                    'afterCreate' => 'The record was created',
                    'afterUpdate' => 'The record was updated',
                    'afterDelete' => 'The record was deleted',
                ],
            ],
        ],
    ],

    'permissions' => [
        'clear_logs' => 'Clear the Activity Log',
        'activities'      => [
            'view_all'    => 'View all activities',
            'view_own'    => 'View own activities',
        ],
    ],

    'settings'    => [
        'description'       => 'Manage EasyAudit Settings',
        'empty_log'         => 'Empty entire audit log',
        'empty_log_confirm' => 'Are you sure you want to empty the audit logs system-wide? This is not reversible!',
        'empty_log_success' => 'The system-wide audit logs have been emptied',
    ],

    'controllers' => [
        'activities' => [
            'label'       => 'Audit Log',
            'description' => 'View the system audit log',
            'filters'     => [
                'created_at' => 'Between dates',
            ],
        ],
    ],

    'widgets' => [
        'myactivities' => [
            'label'      => 'My Activities',
            'no_records' => 'No activities to show. The Eye of Sauron is watching...',
        ],
        'systemactivities' => [
            'label'      => 'System Activities',
            'no_records' => 'No activities to show for the selected filters, try again with a broader perspective.',
        ],
    ],
];
