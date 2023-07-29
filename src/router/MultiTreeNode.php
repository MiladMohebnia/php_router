<?php

namespace miladm\router;

use miladm\router\interface\Group;
use miladm\router\interface\Controller;

class MultiTreeNode
{
    /** @var array<string, MultiTreeNode> $childNodes */
    private array $childNodes = [];

    /** array<Middleware> $middlewareList */
    private array $middlewareList = [];
    private ?Controller $controller = null;

    public function registerSubGroup(string $path, Group $group): void
    {
        // TODO: if path already registered then trigger error
        $newNode = new MultiTreeNode;
        $newNode->bindGroup($group);
        $this->childNodes[$path] = $newNode;
    }

    public function bindGroup(Group $group): void
    {
        $this->middlewareList = $group->middlewareList();
        foreach ($group->controllerList() as $path => $controller) {
            // TODO: detect index path with contoller type support only for setting in the current node
            if ($controller instanceof Controller) {
                $this->registerController($path, $controller);
            } elseif ($controller instanceof Group) {
                $this->registerSubGroup($path, $group);
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
        $this->middlewareList = $controller->middlewareList();
        $this->controller = $controller;
    }
}