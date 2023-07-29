<?php

namespace miladm;

use miladm\oldRouter\router\Router;

class Route
{
    static $router = null;

    public static function bind($path)
    {
        return self::getRouter()->bind($path);
    }

    public static function use($callback)
    {
        return self::getRouter()->globalMiddleware_add($callback);
    }

    public static function get($route, $callback)
    {
        return self::getRouter()->get($route, $callback);
    }

    public static function post($route, $callback)
    {
        return self::getRouter()->post($route, $callback);
    }

    public static function any($route, $callback)
    {
        return self::getRouter()->any($route, $callback);
    }

    public static function alias($route, $callback)
    {
        return self::getRouter()->alias($route, $callback);
    }

    public static function register(array $routeList)
    {
        foreach ($routeList as $route => $callback) {
            self::alias($route, $callback);
        }
    }

    public static function interceptor($callback)
    {
        return self::getRouter()->globalInterceptor_add($callback);
    }

    public static function expect(string $class)
    {
        return self::getRouter()->expect($class);
    }

    public static function run()
    {
        return self::getRouter()->run();
    }

    private static function getRouter(): Router
    {
        if (self::$router === null) {
            self::$router = new Router();
        }
        return self::$router;
    }
}
