<?php
    $route_prefix = config('interact.route_prefix');
    $prefix = trim($route_prefix,'\\\/');
    Route::group([
        'namespace' => 'Milestone\\Interact',
        'prefix' => $prefix,
        'middleware' => \App\Http\Middleware\APIDebug::class,
    ],function(){
        Route::group([
            'prefix' => 'sync'
        ],function(){
            Route::post('delete','SyncController@delete');
            Route::post('{client}/{table}','SyncController@index');
        });
        Route::get('{table_name}','Export@index');
        Route::get('/',function(){ return '<form method="post" enctype="multipart/form-data"><input type="file" name="file"><input type="submit"></form>'; });
        Route::post('/','Import@index');
    });