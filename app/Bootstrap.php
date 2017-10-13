<?php

use System\Config\Config;
use System\Foundation\AliasLoader;
use System\Routing\Router;
use System\View\View;

//--------------------------------------------------------------------------
// Load the Configuration
//--------------------------------------------------------------------------

require APPPATH .'Config.php';

//--------------------------------------------------------------------------
// Boot the Application
//--------------------------------------------------------------------------

// Load the configuration files.
foreach (glob(APPPATH .'Config/*.php') as $path) {
    $key = lcfirst(pathinfo($path, PATHINFO_FILENAME));

    Config::set($key, require_once($path));
}

// Set the Default Timezone.
date_default_timezone_set(Config::get('app.timezone'));

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
