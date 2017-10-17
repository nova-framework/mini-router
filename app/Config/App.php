<?php

return array(
    /**
     * Debug Mode
     */
    'debug' => true, // When enabled the actual PHP errors will be shown.

    /**
     * The Website URL.
     */
    'url' => 'http://www.miniframework.dev/',

    /**
     * Website Name.
     */
    'name' => 'Mini Framework',

    /**
     * The default Timezone for your website.
     * http://www.php.net/manual/en/timezones.php
     */
    'timezone' => 'Europe/London',

    /**
     * The registered Class Aliases.
     */
    'aliases' => array(
        'Config'   => 'System\Config\Config',
        'View'     => 'System\View\View',

        // Facades
        'DB'       => 'System\Support\Facades\DB',
        'Event'    => 'System\Support\Facades\Event',
        'Redirect' => 'System\Support\Facades\Redirect',
        'Request'  => 'System\Support\Facades\Request',
        'Response' => 'System\Support\Facades\Response',
        'Route'    => 'System\Support\Facades\Route',
    ),
);
