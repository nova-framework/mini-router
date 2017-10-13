<?php

use System\Config\Config;
use System\Foundation\Exceptions\Handler as ExceptionHandler;
use System\Foundation\AliasLoader;
use System\Routing\Router;
use System\View\View;

//--------------------------------------------------------------------------
// Load the Configuration
//--------------------------------------------------------------------------

require APPPATH .'Config.php';

// Load the configuration files.
foreach (glob(APPPATH .'Config/*.php') as $path) {
    $key = lcfirst(pathinfo($path, PATHINFO_FILENAME));

    Config::set($key, require_once($path));
}

//--------------------------------------------------------------------------
// Start the Application
//--------------------------------------------------------------------------

// Set the Default Timezone.
$timezone = Config::get('app.timezone');

date_default_timezone_set($timezone);

// Initialize the Exceptions Handler.
ExceptionHandler::initialize();

// Load the Class Aliases.
AliasLoader::initialize();

// Load the Application Routes.
require APPPATH .'Routes.php';

//--------------------------------------------------------------------------
// Dispatch the HTTP Request
//--------------------------------------------------------------------------

$response = Router::dispatch();

// Output the response from Router.
if ($response instanceof View) {
    $response = $response->render();
}

echo $response;
