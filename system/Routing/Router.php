<?php

namespace System\Routing;

use LogicException;


class Router
{
    /**
     * The current Router instance.
     *
     * @var \System\Routing\Router
     */
    protected static $instance;

    /**
     * An array of registered routes.
     *
     * @var array
     */
    protected $routes;

    /**
     * An array of HTTP request methods.
     *
     * @var array
     */
    public static $methods = array('GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS');


    protected function __construct()
    {
        foreach (static::$methods as $method) {
            $this->routes[$method] = array();
        }
    }

    protected function match($methods, $path, $action)
    {
        $methods = array_map('strtoupper', (array) $methods);

        if (in_array('GET', $methods) && ! in_array('HEAD', $methods)) {
            $methods[] = 'HEAD';
        }

        foreach ($methods as $method) {
            if (! in_array($method, static::$methods)) {
                continue;
            }

            $this->routes[$method][$path] = $action;
        }
    }

    protected function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

        // Get the registered routes for the current HTTP method.
        $routes = isset($this->routes[$method]) ? $this->routes[$method] : array();

        foreach ($routes as $route => $action) {
            list ($pattern, $variables) = $this->compileRoute($route);

            // Match the route pattern against the URI.
            if (preg_match($pattern, $path, $matches) !== 1) {
                continue;
            }

            $parameters = array_filter(array_slice($matches, 1), function ($key) use ($variables)
            {
                return in_array($key, $variables);

            }, ARRAY_FILTER_USE_KEY);

            // Execute the Controller's Action.
            list ($controller, $method) = explode('@', $action);

            if (! method_exists($instance = new $controller(), $method)) {
                throw new LogicException("Controller [$controller] has no method named [$method].");
            }

            return call_user_func_array(array($instance, $method), $parameters);
        }

        // If we reached there, no route was found for the current request.
        echo "<h1>Page not found (404)</h1>";
    }

    protected function compileRoute($route)
    {
        $pattern = '/' .trim($route, '/');

        //
        $optionals = 0;

        $variables = array();

        $callback = function ($matches) use ($pattern, &$optionals, &$variables)
        {
            @list($text, $name, $condition, $optional) = $matches;

            if (in_array($name, $variables)) {
                throw new LogicException("Route pattern [$pattern] cannot reference variable name [$name] more than once.");
            }

            $variables[] = $name;

            $regexp = sprintf('/(?P<%s>%s)', $name, ! empty($condition)
                ? str_replace(array('num', 'all'), array('(\d+)', '(.*)'), $condition)
                : '[^/]+');

            if ($optional) {
                $regexp = "(?:$regexp";

                $optionals++;
            } else if ($optionals > 0) {
                throw new LogicException("Route pattern [$pattern] cannot reference variable [$name] after one or more optionals.");
            }

            return $regexp;
        };

        $result = preg_replace_callback('#/\{(.*?)(?:\:(.+?))?(\?)?\}#', $callback, $pattern);

        $regexp = '#^' .$result .str_repeat(')?', $optionals) .'$#i';

        return array($regexp, $variables);
    }

    public static function __callStatic($method, $parameters)
    {
        if (! isset(static::$instance)) {
            static::$instance = new static;
        }

        if (in_array(strtoupper($method), static::$methods)) {
            array_unshift($parameters, $method);

            $method = 'match';
        }

        return call_user_func_array(array(static::$instance, $method), $parameters);
    }
}
