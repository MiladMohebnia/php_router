<?php

namespace miladm\router;

use miladm\router\Controller;
use miladm\router\Group;
use miladm\router\interface\Middleware;

class Router
{
    private static ?MultiTreeNode $tree;

    static function middleware(Middleware $middleware)
    {
    }

    static function group(string $path, Group $group): void
    {
        $indexRoute = self::getTree();
        $indexRoute->registerSubGroup($path, $group);
    }

    static function controller(string $path, Controller $controller): void
    {
        $indexRoute = self::getTree();
        $indexRoute->registerController($path, $controller);
    }

    private static function getTree(): MultiTreeNode
    {
        if (!isset(self::$tree)) {
            self::$tree = new MultiTreeNode;
        }
        return self::$tree;
    }

    static function run()
    {
    }

    static function dump()
    {
        return self::$tree->dump();
    }
}
