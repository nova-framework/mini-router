<?php

defined('DS') || define('DS', DIRECTORY_SEPARATOR);

//--------------------------------------------------------------------------
// Define the absolute paths for Application directories
//--------------------------------------------------------------------------

define('BASEPATH', realpath(__DIR__ .'/../') .DS);

define('WEBPATH', realpath(__DIR__) .DS);

define('APPPATH', BASEPATH .'app' .DS);

//--------------------------------------------------------------------------
// Load the Composer Autoloader
//--------------------------------------------------------------------------

require BASEPATH .'vendor' .DS .'autoload.php';

//--------------------------------------------------------------------------
// Run The Application
//--------------------------------------------------------------------------

use System\Routing\Router;


Router::get('/', 'App\Controllers\Sample@index');

Router::get('pages/{slug?}', 'App\Controllers\Sample@page');

Router::get('blog/{slug:all}', 'App\Controllers\Sample@post');


// Dispatch the HTTP request.
Router::dispatch();
