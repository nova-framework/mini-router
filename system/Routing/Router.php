<?php

namespace System\Routing;

use System\Http\Exceptions\NotFoundHttpException;
use System\Http\Request;
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
        'POST'    => array(),
        'PUT'     => array(),
        'DELETE'  => array(),
        'PATCH'   => array(),
        'HEAD'    => array(),
        'OPTIONS' => array(),
    );

    /**
     * The global parameter patterns.
     *
     * @var array
     */
    protected $patterns = array();


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

        if (! is_array($action)) {
            $action = array('uses' => $action);
        }

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
            $wheres = isset($action['where']) ? $action['where'] : array();

            $pattern = $this->compileRoute($route, array_merge($this->patterns, $wheres));

            if (preg_match($pattern, $path, $matches) !== 1) {
                continue;
            }

            $parameters = array_filter($matches, function ($value, $key)
            {
                return is_string($key) && ! empty($value);

            }, ARRAY_FILTER_USE_BOTH);

            $callback = isset($action['uses']) ? $action['uses'] : $this->findActionClosure($action);

            return $this->call($callback, $parameters);
        }

        throw new NotFoundHttpException('Page not found');
    }

    protected function compileRoute($route, array $patterns)
    {
        $optionals = 0;

        $variables = array();

        $regexp = preg_replace_callback('#/\{(.*?)(\?)?\}#', function ($matches) use ($route, $patterns, &$optionals, &$variables)
        {
            @list(, $name, $optional) = $matches;

            if (in_array($name, $variables)) {
                throw new LogicException("Pattern [$route] cannot reference variable name [$name] more than once.");
            }

            $variables[] = $name;

            $pattern = isset($patterns[$name]) ? $patterns[$name] : '[^/]+';

            if ($optional) {
                $optionals++;

                return sprintf('(?:/(?P<%s>%s)', $name, $pattern);
            } else if ($optionals > 0) {
                throw new LogicException("Pattern [$route] cannot reference variable [$name] after one or more optionals.");
            }

            return sprintf('/(?P<%s>%s)', $name, $pattern);

        }, $route);

        if ($optionals > 0) {
            $regexp .= str_repeat(')?', $optionals);
        }

        return '#^' .$regexp .'$#s';
    }

    protected function findActionClosure(array $action)
    {
        foreach ($action as $key => $value) {
            if (is_numeric($key) && ($value instanceof Closure)) {
                return $value;
            }
        }
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

    public function pattern($key, $pattern)
    {
        $this->patterns[$key] = $pattern;
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
