<?php

namespace System\Routing;

use System\Http\Exceptions\HttpException;
use System\View\View;

use Closure;
use LogicException;


class Router2
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
        'POST'    => array(),
        'PUT'     => array(),
        'DELETE'  => array(),
        'PATCH'   => array(),
        'HEAD'    => array(),
        'OPTIONS' => array(),
    );

    /**
     * The standard parameter patterns.
     *
     * @var array
     */
    protected static $patterns = array(
        '(:num)' => '([0-9]+)',
        '(:any)' => '([^/]+)',
        '(:all)' => '(.*)',
    );

    /**
     * The optional parameter patterns.
     *
     * @var array
     */
    protected static $optional = array(
        '/(:num?)' => '(?:/([0-9]+)',
        '/(:any?)' => '(?:/([^/]+)',
        '/(:all?)' => '(?:/(.*)',
    );


    protected function any($route, $action)
    {
        $methods = array('GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD');

        return $this->match($methods, $route, $action);
    }

    protected function match(array $methods, $route, $action)
    {
        $methods = array_map('strtoupper', $methods);

        if (in_array('GET', $methods) && ! in_array('HEAD', $methods)) {
            $methods[] = 'HEAD';
        }

        $route = '/' .trim($route, '/');

        foreach ($methods as $method) {
            if (array_key_exists($method, $this->routes)) {
                $this->routes[$method][$route] = $action;
            }
        }
    }

    protected function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

        // Get the routes registered for the current HTTP method.
        $routes = $this->routes[$method];

        foreach ($routes as $route => $action) {
            $pattern = $this->compileRoute($route);

            if (preg_match($pattern, $path, $matches) !== 1) {
                continue;
            }

            $parameters = array_filter(array_slice($matches, 1), function ($value)
            {
                return ! empty($value);
            });

            return $this->callAction($action, $parameters);
        }

        throw new HttpException(404, 'Page not found.');
    }

    protected function callAction($action, array $parameters)
    {
        if ($action instanceof Closure) {
            return call_user_func_array($action, $parameters);
        }

        list ($controller, $method) = explode('@', $action);

        if (! class_exists($controller)) {
            throw new LogicException("Controller [$controller] not found.");
        }

        // Create the Controller instance and check its method.
        else if (! method_exists($instance = new $controller(), $method)) {
            throw new LogicException("Controller [$controller] has no method [$method].");
        }

        return $instance->callAction($method, $parameters);
    }

    protected function compileRoute($route)
    {
        $optionals = 0;

        // Process for the optional parameters.
        $pattern = str_replace(
            array_keys(static::$optional), array_values(static::$optional), $route, $optionals
        );

        // Process for the standard parameters.
        $pattern = strtr($pattern, static::$patterns);

        if ($optionals > 0) {
            $pattern .= str_repeat(')?', $optionals);
        }

        return '#^' .$pattern .'$#s';
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
