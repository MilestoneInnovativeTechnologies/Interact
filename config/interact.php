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

    'max_same_request' => 0
];
