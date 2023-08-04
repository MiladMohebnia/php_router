<?php

namespace miladm\router;

use Exception;
use miladm\router\exceptions\ControllerNotFound;
use miladm\router\exceptions\InvalidControllerType;
use miladm\router\RequestMethod;
use miladm\router\Group;
use miladm\router\interface\Middleware;
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
        $path = $this->pathFormatter($path);
        $this->childNodes[$path] = $newNode;
    }

    public function bindGroup(Group $group): void
    {
        if ($group instanceof UseMiddleware) {
            $this->middlewareList = $group->middlewareList();
        }
        foreach ($group->controllerList() as $path => $controllerOrGroup) {
            // TODO: detect index path with contoller type support only for setting in the current node
            $path = $this->pathFormatter($path);
            if (is_array($controllerOrGroup)) {
                foreach ($controllerOrGroup as $controller) {
                    if ($controller instanceof Controller) {
                        $this->registerController($path, $controller);
                    }
                }
            } elseif ($controllerOrGroup instanceof Controller) {
                $this->registerController($path, $controllerOrGroup);
            } elseif ($controllerOrGroup instanceof Group) {
                $this->registerSubGroup($path, $controllerOrGroup);
            } else {
                throw new InvalidControllerType($controllerOrGroup, $path);
            }
        }
    }

    public function registerController(string $path, Controller $controller): void
    {
        $path = $this->pathFormatter($path);
        $newNode = isset($this->childNodes[$path]) ? $this->childNodes[$path] : new MultiTreeNode;
        $newNode->bindController($controller);
        $this->childNodes[$path] = $newNode;
    }

    public function bindController(Controller $controller): void
    {
        $requestMethod = $controller->requestMethod()->value;
        $this->controller[$requestMethod] = $controller;
    }

    public function addMiddleware(Middleware $middleware): void
    {
        $this->middlewareList[] = $middleware;
    }

    public function getController(
        string $path,
        RequestMethod $requestMethod,
        bool $childMethod = false
    ): Controller | false {
        $path = $this->pathFormatter($path);
        $explodedPath = explode("/", $path);

        if ($path === "" && isset($this->controller[$requestMethod->value])) {
            return $this->controller[$requestMethod->value];
        }

        $childNodePath = array_shift($explodedPath);
        if (
            ($path === "" && !isset($this->controller[$requestMethod->value]))
            || !isset($this->childNodes[$childNodePath])
        ) {
            return $this->controllerNotFoundIfNeeded($path, $childMethod);
        }

        $newPath = implode("/", $explodedPath);
        return
            $this->childNodes[$childNodePath]->getController($newPath, $requestMethod, true)
            ?: $this->controllerNotFoundIfNeeded($path, $childMethod);
    }

    private function controllerNotFoundIfNeeded($path, $childMethod): false
    {
        if (!$childMethod) {
            throw new ControllerNotFound($path);
        }
        return false;
    }

    public function dump(): array
    {
        $output = [];
        foreach ($this->childNodes as $key => $node) {
            $output[$key] = $node->dump();
        }
        $output['_middlewareList'] = $this->middlewareList;
        $output['_controller'] = $this->controller;
        return $output;
    }

    private function pathFormatter(string $path): string
    {
        if (strlen($path) > 0 && $path[0] === '/') {
            $path = substr($path, 1);
        }
        $length = strlen($path);
        if ($length > 0 && $path[$length - 1] === '/') {
            $path = substr($path, 0, $length - 1);
        }
        if ($path === 'index') {
            $path = '';
        }
        return $path;
    }
}
