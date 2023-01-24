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
    | Track changes in the source model's attributes
    |--------------------------------------------------------------------------
    |
    | By default, changes that are made to the source model's attributes
    | will be tracked and made available for reverting to. Set this to
    | false in order to disable that behaviour.
    |
    */
    'trackChanges' => true,
];