<?php

// The Routes.
Route::get('/',        'App\Controllers\Sample@index');
Route::get('database', 'App\Controllers\Sample@database');
Route::get('error',    'App\Controllers\Sample@error');


Route::get('pages/{slug?}', 'App\Controllers\Sample@page');

//Route::get('pages/(:any?)', 'App\Controllers\Sample@page');


Route::get('blog/{slug:all}', 'App\Controllers\Sample@post');

//Route::get('blog/(:all?)', 'App\Controllers\Sample@post');

