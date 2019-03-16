<?php
    Route::group([
        'namespace' => 'Milestone\\Interact',
        'prefix' => 'interact'
    ],function(){
        Route::get('/',function(){ return '<form method="post" enctype="multipart/form-data"><input type="file" name="file"><input type="submit"></form>'; });
        Route::post('/','Controller@index');
    });