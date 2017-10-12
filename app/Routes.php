<?php

// The Routes.
Route::get('/', 'App\Controllers\Sample@index');

Route::get('pages/{slug?}', 'App\Controllers\Sample@page');

Route::get('blog/{slug:all}', 'App\Controllers\Sample@post');
