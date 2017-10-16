<?php

//
// The global parameter patterns.

Route::pattern('slug', '.*');


//
// The application routes.

Route::get('/',        'App\Controllers\Sample@index');
Route::get('database', 'App\Controllers\Sample@database');
Route::get('error',    'App\Controllers\Sample@error');
Route::get('test',     'App\Controllers\Sample@test');


Route::get('pages/{page?}', 'App\Controllers\Sample@page');
//Route::get('pages/(:any?)', 'App\Controllers\Sample@page');

Route::get('blog/{slug}', 'App\Controllers\Sample@post');
//Route::get('blog/(:all)', 'App\Controllers\Sample@post');


// A route executing a closure.
Route::get('test', function ()
{
    echo 'This is a test.';
});

// A route executing a closure and having own parameter patterns.
Route::get('language/{code}', array('uses' => function ($code)
{
    echo htmlentities($code);

}, 'where' => array('code' => '[a-z]{2}')));
