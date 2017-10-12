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

use System\Config\Config;
use System\Foundation\AliasLoader;
use System\Routing\Router;
use System\View\View;

// Load the configuration
foreach (glob(APPPATH .'Config/*.php') as $path) {
    $key = lcfirst(pathinfo($path, PATHINFO_FILENAME));

    Config::set($key, include_once($path));
}

// Load the Class Aliases.
AliasLoader::initialize();

// Load the Routes.
require APPPATH .'Routes.php';

// Dispatch the request.
$response = Router::dispatch();

// Output the response from Router.
if ($response instanceof View) {
    $response = $response->render();
}

echo $response;
