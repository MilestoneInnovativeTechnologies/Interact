<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The route prefix where to handle all interact requests
    |
    */

    'route_prefix' => 'interact',

    /*
    |--------------------------------------------------------------------------
    | Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace where whole project interact
    | classes kept
    |
    */

    'namespace' => 'Milestone\\Interact',

    /*
    |--------------------------------------------------------------------------
    | Composite key join delimiter
    |--------------------------------------------------------------------------
    |
    | If the interaction from table have composite key
    | then in response, the composite keys get joined
    | using delimiter
    |
    */

    'delimiter' => '/',

    /*
    |--------------------------------------------------------------------------
    | Maximum same request to be ignored
    |--------------------------------------------------------------------------
    |
    | The maximum number of requests to be ignored if the same request comes
    | If requesting data for a particular data with specified time, if same
    | is requesting over and over, then this number is used to ignore further
    | same requests. If 0, no requests will be ignored and on each request
    | DB Check will done.
    |
    */

    'max_same_request' => 0,

    /*
    |--------------------------------------------------------------------------
    | Cache Store
    |--------------------------------------------------------------------------
    |
    | Define cache store for storing table update timing
    |
    */

    'cache_stores' => [
        'interact' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/interact'),
        ],
        'uTable' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/interact/underlyingTable'),
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage disk
    |--------------------------------------------------------------------------
    |
    | Defines a storage disk where interact stores all details regarding
    | synchronization
    |
    */

    'filesystems_disks' => [
        'interact' => [
            'driver' => 'local',
            'root' => storage_path('app/interact'),
        ]
    ]
];
