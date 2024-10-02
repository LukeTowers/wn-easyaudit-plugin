<?php

return [
    'plugin' => [
        'name'        => 'EasyAudit',
        'description' => 'Bekijk en beheer auditlogboeken voor modellen binnen uw project',
    ],

    'models' => [
        'activity' => [
            'label'        => 'Activiteit',
            'label_plural' => 'Activiteiten',
            'audit_log'    => 'Auditlogboek',
            'log'          => 'Logboek',
            'event'        => 'Actie',
            'description'  => 'Beschrijving',
            'subject'      => 'Onderwerp',
            'source'       => 'Bron',
            'source_user'  => 'Gebruiker',
            'created_at'   => 'Activiteitsdatum',
            'ip_address'   => 'IP-adres',
            'url'          => 'URL',
            'changes'      => 'Wijzigingen',
            'no_changes'   => 'Geen wijzigingen vastgelegd door evenement',
            'change_from'  => 'Van',
            'change_to'    => 'Naar',
            'user_agent'   => 'User Agent',
            'tab_request'  => 'Verzoek',
            'unknown'      => 'Onbekend',
            'trackableEvents' => [
                'model' => [
                    'afterCreate' => 'Het record is aangemaakt',
                    'afterUpdate' => 'Het record is bijgewerkt',
                    'afterDelete' => 'Het record is verwijderd',
                ],
            ],
        ],
    ],

    'permissions' => [
        'clear_logs' => 'Wis het Activiteitenlogboek',
        'activities'      => [
            'view_all'    => 'Bekijk alle activiteiten',
            'view_own'    => 'Bekijk eigen activiteiten',
        ],
    ],

    'settings'    => [
        'description'       => 'Beheer EasyAudit-instellingen',
        'empty_log'         => 'Leeg het volledige auditlogboek',
        'empty_log_confirm' => 'Weet u zeker dat u de auditlogboeken systeem-breed wilt legen? Dit is niet omkeerbaar!',
        'empty_log_success' => 'De systeem-brede auditlogboeken zijn geleegd',
    ],

    'controllers' => [
        'activities' => [
            'label'       => 'Auditlogboek',
            'description' => 'Bekijk het systeem auditlogboek',
            'filters'     => [
                'created_at' => 'Tussen datums',
            ],
        ],
    ],

    'widgets' => [
        'myactivities' => [
            'label'      => 'Mijn Activiteiten',
            'no_records' => 'Geen activiteiten om te tonen. Het Oog van Sauron kijkt mee...',
        ],
        'systemactivities' => [
            'label'      => 'Systeemactiviteiten',
            'no_records' => 'Geen activiteiten om te tonen voor de geselecteerde filters, probeer opnieuw met een bredere kijk.',
        ],
    ],
];
