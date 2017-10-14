<?php

namespace System\Foundation;

use System\Config\Config;

use RuntimeException;


class AliasLoader
{

    /**
     * Bootstrap the Aliases Loader.
     *
     * @return void
     */
    public static function bootstrap()
    {
        $classes = Config::get('app.aliases', array());

        foreach ($classes as $classAlias => $className) {
            // This ensures the alias is created in the global namespace.
            $classAlias = '\\' .ltrim($classAlias, '\\');

            // Check if the Class already exists.
            if (class_exists($classAlias)) {
                // Bail out, a Class already exists with the same name.
                throw new RuntimeException('A class [' .$classAlias .'] already exists with the same name.');
            }

            class_alias($className, $classAlias);
        }
    }
}
