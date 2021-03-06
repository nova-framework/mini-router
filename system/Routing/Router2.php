<?php

namespace System\Routing;

use System\Http\Exceptions\NotFoundHttpException;
use System\Http\Request;
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


    /**
     * Get a Events Dispatcher instance.
     *
     * @return \System\Routing\Router
     */
    public static function getInstance()
    {
        if (isset(static::$instance)) {
            return static::$instance;
        }

        return static::$instance = new static();
    }

    public function any($route, $action)
    {
        $methods = array('GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD');

        return $this->match($methods, $route, $action);
    }

    public function match(array $methods, $route, $action)
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

    public function dispatch(Request $request)
    {
        $method = $request->method();

        $path = $request->path();

        // Get the routes by HTTP method.
        $routes = isset($this->routes[$method]) ? $this->routes[$method] : array();

        foreach ($routes as $route => $action) {
            $pattern = $this->compileRoute($route);

            if (preg_match($pattern, $path, $matches) !== 1) {
                continue;
            }

            $parameters = array_filter(array_slice($matches, 1), function ($value)
            {
                return ! empty($value);
            });

            return $this->call($action, $parameters);
        }

        throw new NotFoundHttpException('Page not found');
    }

    protected function compileRoute($route)
    {
        $searches = array_keys(static::$optional);
        $replaces = array_values(static::$optional);

        $result = str_replace($searches, $replaces, $route, $optionals);

        if ($optionals > 0) {
            $result .= str_repeat(')?', $optionals);
        }

        return '#^' .strtr($result, static::$patterns) .'$#s';
    }

    protected function call($callback, array $parameters)
    {
        if ($callback instanceof Closure) {
            return call_user_func_array($callback, $parameters);
        }

        list ($controller, $method) = explode('@', $callback);

        if (! class_exists($controller)) {
            throw new LogicException("Controller [$controller] not found.");
        }

        // Create the Controller instance and check the specified method.
        else if (! method_exists($instance = new $controller(), $method)) {
            throw new LogicException("Controller [$controller] has no method [$method].");
        }

        return $instance->callAction($method, $parameters);
    }

    public function __call($method, $parameters)
    {
        if (array_key_exists($key = strtoupper($method), $this->routes)) {
            array_unshift($parameters, array($key));

            $method = 'match';
        }

        return call_user_func_array(array($this, $method), $parameters);
    }
}
