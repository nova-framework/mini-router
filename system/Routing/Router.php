<?php

namespace System\Routing;

use System\View\View;

use Closure;
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
    protected $routes = array(
        'GET'     => array(),
        'HEAD'    => array(),
        'POST'    => array(),
        'PUT'     => array(),
        'PATCH'   => array(),
        'DELETE'  => array(),
        'OPTIONS' => array(),
    );


    protected function match(array $methods, $path, $action)
    {
        $methods = array_map('strtoupper', $methods);

        if (in_array('GET', $methods) && ! in_array('HEAD', $methods)) {
            $methods[] = 'HEAD';
        }

        foreach ($methods as $method) {
            if (! array_key_exists($method, $this->routes)) {
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

        foreach ($routes as $route => $callback) {
            list ($pattern, $variables) = $this->compileRoute($route);

            // Match the route pattern against the URI.
            if (preg_match($pattern, $path, $matches) !== 1) {
                continue;
            }

            $parameters = array_filter(array_slice($matches, 1), function ($key) use ($variables)
            {
                return in_array($key, $variables);

            }, ARRAY_FILTER_USE_KEY);

            if ($callback instanceof Closure) {
                return call_user_func_array($callback, $parameters);
            }

            // Execute the Controller's Action.
            list ($controller, $method) = explode('@', $callback);

            if (! class_exists($controller)) {
                throw new LogicException("Controller [$controller] does not exists.");
            }

            // Create the Controller instance and check for its method.
            else if (! method_exists($instance = new $controller(), $method)) {
                throw new LogicException("Controller [$controller] has no method named [$method].");
            }

            return $instance->callAction($method, $parameters);
        }

        // If we reached there, no route was found for the current request.
        return View::make('Errors/404')->render();
    }

    protected function compileRoute($route)
    {
        $pattern = '/' .trim($route, '/');

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

    public static function getInstance()
    {
        if (isset(static::$instance)) {
            return static::$instance;
        }

        return static::$instance = new static();
    }

    public static function __callStatic($method, $parameters)
    {
        $instance = static::getInstance();

        if (array_key_exists($httpMethod = strtoupper($method), $instance->routes)) {
            array_unshift($parameters, array($httpMethod));

            $method = 'match';
        }

        return call_user_func_array(array($instance, $method), $parameters);
    }
}
