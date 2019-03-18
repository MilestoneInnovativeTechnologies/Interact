<?php
    $route_prefix = config('interact.route_prefix');
    $prefix = trim($route_prefix,'\\/');
    Route::group([
        'namespace' => 'Milestone\\Interact',
        'prefix' => $prefix,
    ],function(){
        Route::get('/',function(){ return '<form method="post" enctype="multipart/form-data"><input type="file" name="file"><input type="submit"></form>'; });
        Route::post('/','Controller@index');
    });