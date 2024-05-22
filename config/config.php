<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Log Request's IP Address
    |--------------------------------------------------------------------------
    |
    | By default, the IP address that is making the request that triggers
    | the logging action will get logged. Set this to false in order to
    | disable that behaviour.
    |
    */

    'logIpAddress' => true,

    /*
    |--------------------------------------------------------------------------
    | Log Request's User Agent
    |--------------------------------------------------------------------------
    |
    | By default, the UserAgent that is making the request that triggers
    | the logging action will get logged. Set this to false in order to
    | disable that behaviour.
    |
    */

    'logUserAgent' => true,

    /*
    |--------------------------------------------------------------------------
    | Log Request's Referrer
    |--------------------------------------------------------------------------
    |
    | By default, the URL for the request that triggers the logging action
    | will get logged. Set this to false in order to
    | disable that behaviour.
    |
    */

    'logUrl' => true,

    /*
    |--------------------------------------------------------------------------
    | Track changes in the source model's attributes
    |--------------------------------------------------------------------------
    |
    | By default, changes that are made to the source model's attributes
    | will be tracked and made available for reverting to. Set this to
    | false in order to disable that behaviour.
    |
    */

    'trackChanges' => true,

    /*
    |--------------------------------------------------------------------------
    | Automatically add the activities FormWidget to TrackableModel forms
    |--------------------------------------------------------------------------
    |
    | By default, the "activities" FormWidget will be automatically added to
    | any forms whose model's implement the TrackableModel behaviour. Set
    | this to false in order to disable that behaviour.
    |
    */

    'autoInjectActvitiesFormWidget' => true,

    /*
    |--------------------------------------------------------------------------
    | Models to Track
    |--------------------------------------------------------------------------
    |
    | By default, changes that are made to the source model's attributes
    | will be tracked and made available for reverting to. Set this to
    | false in order to disable that behaviour.
    |
    */

    'modelsToTrack' => [
        // Track changes to theme settings
        \Cms\Models\ThemeData::class,
        \Backend\Models\User::class,

        // Example of per model configuration options
        // \Cms\Models\ThemeData::class => [
        //     'ignoredAttributes' => ['title'],
        // ],
    ],

];
