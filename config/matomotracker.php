<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Matomo URL
    |--------------------------------------------------------------------------
    |
    | The URL of your Matomo install.
    |
    */
    'url' => env('MATOMO_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Matomo site ID
    |--------------------------------------------------------------------------
    |
    | The id of the site that should be tracked.
    |
    */
    'idSite' => env('MATOMO_SITE_ID', 1),

    /*
    |--------------------------------------------------------------------------
    | Matomo auth token
    |--------------------------------------------------------------------------
    |
    | The auth token of your user.
    |
    */
    'tokenAuth' => env('MATOMO_AUTH_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | For queuing the tracking you can use custom queue names.
    | Use 'default' if you want to run the queued items within the standard queue.
    |
    */
    'queue' => env('MATOMO_QUEUE', 'matomotracker'),

    /*
    |--------------------------------------------------------------------------
    | Queue connection
    |--------------------------------------------------------------------------
    |
    | Optionally set a custom queue connection. Laravel defaults to "sync".
    |
    */
    'queueConnection' => env('MATOMO_QUEUE_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Async
    |--------------------------------------------------------------------------
    |
    | This function will use the laravel queue.
    | Make sure your setup is correct.
    |
    */
    'async' => false,
];
