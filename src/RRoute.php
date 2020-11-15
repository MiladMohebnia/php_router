<?php

namespace miladm;

use miladm\router\Router;
use miladm\router\RRouter;

class RRoute
{
    static $router = null;

    public static function bind($path)
    {
        $router = self::router();
        return $router->bind($path);
    }



    public static function use($callback)
    {
        $router = self::router();
        return $router->globalMiddleware_add($callback);
    }

    public static function get($route, $callback)
    {
        $router = self::router();
        return $router->get($route, $callback);
    }

    public static function post($route, $callback)
    {
        $router = self::router();
        return $router->post($route, $callback);
    }

    public static function any($route, $callback)
    {
        $router = self::router();
        return $router->any($route, $callback);
    }

    public static function alias($route, $callback)
    {
        $router = self::router();
        return $router->alias($route, $callback);
    }

    public static function register(array $routeList)
    {
        foreach ($routeList as $route => $callback) {
            self::alias($route, $callback);
        }
    }

    public static function interceptor($callback)
    {
        $router = self::router();
        return $router->globalInterceptor_add($callback);
    }

    public static function expect(string $class)
    {
        $router = self::router();
        return $router->expect($class);
    }

    public static function run()
    {
        $router = self::router();
        return $router->run();
    }

    private static function router(): RRouter
    {
        if (self::$router === null) {
            self::$router = new RRouter();
        }
        return self::$router;
    }
}
