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


    protected function any($route, $action)
    {
        $methods = array('GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE');

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
            if (! array_key_exists($method, $this->routes)) {
                continue;
            }

            $this->routes[$method][$route] = $action;
        }
    }

    protected function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

        foreach ($this->getRoutes($method) as $route => $action) {
            list ($pattern, $variables) = $this->compileRoute($route);

            if (preg_match($pattern, $path, $matches) === 1) {
                // Extract the (named) parameters from matches.

                $parameters = array_filter($matches, function ($key) use ($variables)
                {
                    return in_array($key, $variables);

                }, ARRAY_FILTER_USE_KEY);

                return $this->callAction($action, $parameters);
            }
        }

        // If we reached here, no route was found for the current HTTP request.

        return View::make('Errors/404')->render();
    }

    protected function callAction($callback, $parameters)
    {
        if ($callback instanceof Closure) {
            return call_user_func_array($callback, $parameters);
        }

        list ($controller, $method) = explode('@', $callback);

        if (! method_exists($instance = new $controller(), $method)) {
            throw new LogicException("Controller [$controller] has no method [$method].");
        }

        return $instance->callAction($method, $parameters);
    }

    protected function compileRoute($route)
    {
        $optionals = 0;

        $variables = array();

        // Prepare the pattern and compute the associated regular expression.
        $result = preg_replace_callback('#/\{(.*?)(?:\:(.+?))?(\?)?\}#', function ($matches) use ($route, &$optionals, &$variables)
        {
            @list($text, $name, $condition, $optional) = $matches;

            if (in_array($name, $variables)) {
                throw new LogicException("Route pattern [$route] cannot reference variable name [$name] more than once.");
            }

            $variables[] = $name;

            $regexp = sprintf('/(?P<%s>%s)', $name, ! empty($condition)
                ? str_replace(array('num', 'all'), array('\d+', '.*'), $condition)
                : '[^/]+');

            if ($optional) {
                $regexp = "(?:$regexp";

                $optionals++;
            } else if ($optionals > 0) {
                throw new LogicException("Route pattern [$route] cannot reference variable [$name] after one or more optionals.");
            }

            return $regexp;

        }, $route);

        $pattern = '#^' .$result .str_repeat(')?', $optionals) .'$#s';

        return array($pattern, $variables);
    }

    protected function getRoutes($method = null)
    {
        if (! is_null($method)) {
            return $this->routes[$method];
        }

        return $this->routes;
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

        if (array_key_exists($key = strtoupper($method), $instance->getRoutes())) {
            array_unshift($parameters, array($key));

            $method = 'match';
        }

        return call_user_func_array(array($instance, $method), $parameters);
    }
}
