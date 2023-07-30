<?php

namespace miladm\router;

use miladm\router\RequestMethod;
use miladm\router\interface\Group;
use miladm\router\Controller;
use miladm\router\interface\UseMiddleware;

class MultiTreeNode
{
    /** @var array<string, MultiTreeNode> $childNodes */
    private array $childNodes = [];

    /** array<Middleware> $middlewareList */
    private array $middlewareList = [];

    /** array<RequestMethod, Controller> $middlewareList */
    private array $controller = [];

    public function registerSubGroup(string $path, Group $group): void
    {
        // TODO: if path already registered then trigger error
        $newNode = new MultiTreeNode;
        $newNode->bindGroup($group);
        $this->childNodes[$path] = $newNode;
    }

    public function bindGroup(Group $group): void
    {
        if ($group instanceof UseMiddleware) {
            $this->middlewareList = $group::middlewareList();
        }
        foreach ($group::controllerList() as $path => $controllerOrGroup) {
            // TODO: detect index path with contoller type support only for setting in the current node
            if ($controllerOrGroup instanceof Controller) {
                $this->registerController($path, $controllerOrGroup);
            } elseif ($controllerOrGroup instanceof Group) {
                $this->registerSubGroup($path, $controllerOrGroup);
            }
        }
    }

    public function registerController(string $path, Controller $controller): void
    {
        // TODO: if path already registered then trigger error
        $newNode = new MultiTreeNode;
        $newNode->bindController($controller);
        $this->childNodes[$path] = $newNode;
    }

    public function bindController(Controller $controller): void
    {
        if ($controller instanceof UseMiddleware) {
            $this->middlewareList = $controller::middlewareList();
        }
        $this->controller[$controller::requestMethod()->value] = $controller;
    }
}
