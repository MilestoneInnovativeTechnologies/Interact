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

    'delimiter' => '/'
];
