<?php

namespace miladm\router;

use miladm\router\Controller;
use miladm\router\exceptions\ControllerNotFound;
use miladm\router\Group;
use miladm\router\interface\Middleware;
use miladm\router\interface\UseMiddleware;

class Router
{
    private static ?MultiTreeNode $tree;

    static function middleware(Middleware $middleware): void
    {
        self::getTree()->addMiddleware($middleware);
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
            self::reset();
        }
        return self::$tree;
    }

    static function run()
    {
        try {
            $controllerNode = self::getTree()->findControllerNode(new Request);
            $request = $controllerNode->getRequest();
            $middlewareList = $controllerNode->getMiddlewareList();
            $controller = $controllerNode->getController($request->getRequestMethod());
            if ($controller instanceof UseMiddleware) {
                $middlewareList = array_merge($middlewareList, $controller->middlewareList());
            }
            $next = function ($request) use ($controller) {
                return $controller->handler($request);
            };
            for ($i = count($middlewareList) - 1; $i >= 0; $i--) {
                $next = function ($request) use ($next, $middlewareList, $i) {
                    return $middlewareList[$i]->handler($request, $next);
                };
            }
            $response = $next($request);
        } catch (ControllerNotFound $e) {
            $response = $e->showErrorPage();
        }
        self::showResponse($response);
    }

    static function showResponse(string|array|object $response)
    {
        if (is_string($response)) {
            echo $response;
        } else {
            @header('Content-Type: application/json');
            echo json_encode($response);
        }
    }

    static function dump(): array
    {
        return self::$tree->dump();
    }

    static function reset(): void
    {
        self::$tree = new MultiTreeNode;
    }
}
