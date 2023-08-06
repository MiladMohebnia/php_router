<?php

namespace miladm\router;

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

    /** @var array<string, MultiTreeNode> $dynamicPathNodeList */
    private array $dynamicPathNodeList = [];

    public function registerSubGroup(string $path, Group $group): void
    {
        $path = $this->pathFormatter($path);
        if ($path === '') {
            $this->bindGroup($group);
            return;
        }
        if (strpos($path, "/") > 0) {
            $explodedPath = explode("/", $path);
            $path = array_shift($explodedPath);
            $restOfPath = implode("/", $explodedPath);
        }
        $newNode = $this->getNodeOrCreateNew($path);
        if (isset($restOfPath)) {
            $newNode->registerSubGroup($restOfPath, $group);
        } else {
            $newNode->bindGroup($group);
        }
        $this->registerNode($path, $newNode);
    }

    public function bindGroup(Group $group): void
    {
        if ($group instanceof UseMiddleware) {
            $this->middlewareList = array_merge($this->middlewareList, $group->middlewareList());
        }
        foreach ($group->controllerList() as $path => $controllerOrGroup) {
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
        if (strpos($path, "/") > 0) {
            $explodedPath = explode("/", $path);
            $path = array_shift($explodedPath);
            $restOfPath = implode("/", $explodedPath);
        }
        $newNode = $this->getNodeOrCreateNew($path);
        if (isset($restOfPath)) {
            $newNode->registerController($restOfPath, $controller);
        } else {
            $newNode->bindController($controller);
        }

        $this->registerNode($path, $newNode);
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
        RequestMethod $requestMethod
    ) {
        if (isset($this->controller[$requestMethod->value])) {
            return $this->controller[$requestMethod->value];
        }
        throw new ControllerNotFound('');
    }

    public function findController(
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
        $newPath = implode("/", $explodedPath);

        if ($path === "" && !isset($this->controller[$requestMethod->value])) {
            return $this->controllerNotFoundIfNeeded($path, $childMethod);
        }
        if (!isset($this->childNodes[$childNodePath])) {
            if (count($this->dynamicPathNodeList) == 0) {
                return $this->controllerNotFoundIfNeeded($path, $childMethod);
            }
            foreach ($this->dynamicPathNodeList as $node) {
                $controller = $node->findController($newPath, $requestMethod, true)
                    ?: false;
                if (!$controller) {
                    continue;
                }
                break;
            }
            return $controller ?: $this->controllerNotFoundIfNeeded($path, $childMethod);
        }

        return
            $this->childNodes[$childNodePath]?->findController($newPath, $requestMethod, true)
            ?: $this->controllerNotFoundIfNeeded($path, $childMethod);
    }

    public function findControllerNode(
        string $path,
        RequestMethod $requestMethod,
        bool $childMethod = false
    ): self | false {
        $path = $this->pathFormatter($path);
        $explodedPath = explode("/", $path);

        if ($path === "" && isset($this->controller[$requestMethod->value])) {
            return $this;
        }

        $childNodePath = array_shift($explodedPath);
        $newPath = implode("/", $explodedPath);

        if ($path === "" && !isset($this->controller[$requestMethod->value])) {
            return $this->controllerNotFoundIfNeeded($path, $childMethod);
        }
        if (!isset($this->childNodes[$childNodePath])) {
            if (count($this->dynamicPathNodeList) == 0) {
                return $this->controllerNotFoundIfNeeded($path, $childMethod);
            }
            foreach ($this->dynamicPathNodeList as $node) {
                $node = $node->findControllerNode($newPath, $requestMethod, true)
                    ?: false;
                if (!$node) {
                    continue;
                }
                break;
            }
            return $node ?: $this->controllerNotFoundIfNeeded($path, $childMethod);
        }

        return
            $this->childNodes[$childNodePath]?->findControllerNode($newPath, $requestMethod, true)
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
        foreach ($this->dynamicPathNodeList as $key => $node) {
            $output['_dynamicPathNodeList'][$key] = $node->dump();
        }
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

    private function getNodeOrCreateNew(string $path): MultiTreeNode
    {
        if (strpos($path, ":") !== false) {
            return isset($this->dynamicPathNodeList[$path]) ? $this->dynamicPathNodeList[$path] : new MultiTreeNode;
        } else {
            return isset($this->childNodes[$path]) ? $this->childNodes[$path] : new MultiTreeNode;
        }
    }

    private function registerNode(string $path, MultiTreeNode $node)
    {
        // detect invalid character for path
        if (strpos($path, ":") !== false) {
            $this->dynamicPathNodeList[$path] = $node;
        } else {
            $this->childNodes[$path] = $node;
        }
    }
}
