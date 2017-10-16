<?php

use System\Config\Config;
use System\Foundation\AliasLoader;
use System\Routing\Router;

use App\Exceptions\Handler as ExceptionHandler;


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

    Config::set($key, require($path));
}

//--------------------------------------------------------------------------
// Start the Application
//--------------------------------------------------------------------------

// Set the Default Timezone.
$timezone = Config::get('app.timezone', 'Europe/London');

date_default_timezone_set($timezone);

// Initialize the Exceptions Handler.
ExceptionHandler::initialize();

// Initialize the Aliases Loader.
AliasLoader::initialize();

// Load the Routes.
require APPPATH .'Routes.php';

//--------------------------------------------------------------------------
// Dispatch the HTTP Request
//--------------------------------------------------------------------------

$response = Router::dispatch();

// Display the response.
echo $response;
