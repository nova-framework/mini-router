<?php

use System\Config\Config;
use System\Foundation\Exceptions\Handler as ExceptionHandler;
use System\Foundation\AliasLoader;
use System\Routing\Router;
use System\View\View;


//--------------------------------------------------------------------------
// Setup the Errors Reporting
//--------------------------------------------------------------------------

error_reporting(-1);

ini_set('display_errors', 'Off');

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
$timezone = Config::get('app.timezone', 'Europe/London');

date_default_timezone_set($timezone);

// Bootstrap the Exceptions Handler.
ExceptionHandler::bootstrap();

// Bootstrap the Aliases Loader.
AliasLoader::bootstrap();

// Load the Application Routes.
require APPPATH .'Routes.php';

//--------------------------------------------------------------------------
// Dispatch the HTTP Request
//--------------------------------------------------------------------------

$response = Router::dispatch();

// Display the response received from Routing.
if ($response instanceof View) {
    echo $response->render();
} else {
    echo $response;
}
