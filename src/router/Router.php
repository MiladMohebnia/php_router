<?php

namespace miladm\router;

use Exception;
use miladm\router\exceptions\RequestException;

class Router
{
    private $request;
    private $skip = false;
    private $globalMiddlewareList = [];
    private $middlewareList = [];
    private $globalInterceptorList = [];
    private $interceptorList = [];
    private $callback = null;
    private $bind = null;
    private $bind_activeState = false;

    function __construct()
    {
        $this->request = new Request();
    }

    public function bind(string $path)
    {
        if ($path == '') {
            $this->bind_activeState = true;
            $this->registerBind($path);
            return $this;
        }
        if ($path[strlen($path) - 1] !== '/') {
            $path .= '/';
        }
        if ($path[0] === '/') {
            $path = substr($path, 1);
        }
        $this->registerBind($path);
        $checkPath = $path . ".*";
        $this->bind_activeState = $this->request->checkIfMatch($checkPath);
        if (!$this->hasBind()) {
            return $this->skip();
        }
        return $this;
    }

    public function controller(string $controllerClassName)
    {
        if (!$this->hasBind()) {
            return $this;
        }
        $this->register($this->bind, $controllerClassName);
        return $this;
    }

    public function get($route, $callback)
    {
        if ($this->request->method !== 'GET') {
            return $this->skip();
        }
        if ($this->bind !== null) {
            if ($route != '' && $route[0] == '/') {
                $route = substr($route, 1);
            }
            $route = $this->bind . $route;
        }
        if (!$this->request->checkIfMatch($route)) {
            return $this->skip();
        }
        $this->register($route, $callback);
        return $this;
    }

    public function post($route, $callback)
    {
        if ($this->request->method !== 'POST') {
            return $this->skip();
        }
        if ($this->bind !== null) {
            if ($route != '' && $route[0] == '/') {
                $route = substr($route, 1);
            }
            $route = $this->bind . $route;
        }
        if (!$this->request->checkIfMatch($route)) {
            return $this->skip();
        }
        $this->register($route, $callback);
        return $this;
    }

    public function any($route, $callback)
    {
        if ($this->bind !== null) {
            if ($route != '' && $route[0] == '/') {
                $route = substr($route, 1);
            }
            $route = $this->bind . $route;
        }
        if (!$this->request->checkIfMatch($route)) {
            return $this->skip();
        }
        $this->register($route, $callback);
        return $this;
    }

    public function alias($route, $callback)
    {
        if ($this->callback) {
            return $this->skip();
        }
        if (!$this->request->checkIfMatch_alias($route)) {
            return $this->skip();
        }
        $this->register($route, $callback);
        return $this;
    }

    private function registerBind($path)
    {
        $this->bind = $path;
    }

    private function hasBind()
    {
        return $this->bind_activeState;
    }

    private function register($route, $callback)
    {
        $this->skip = false;
        if (is_string($callback)) {
            if (!is_callable($callback)) {
                if (class_exists($callback)) {
                    $targetMethodList = $this->generatePossibleMethodNameList($route, $callback);
                    foreach ($targetMethodList as $callback) {
                        $this->callback = null;
                        if (is_callable($callback)) {
                            return $this->callback = $callback;
                        }
                    }
                    return false;
                } else {
                    trigger_error('bad router configuration');
                }
            }
        }
        $this->callback = $callback;
    }

    private function generatePossibleMethodNameList($route, $className)
    {
        $targetMethod = str_replace("_", ";", $this->request->path);
        $targetMethod = $this->convertToMethodName($targetMethod);
        $convertedRoute = $this->convertToMethodName($route);
        if (strlen($targetMethod) > 0) {
            $aliasMethod = "";
        }
        if ($convertedRoute !== '') {
            $routePositionInTargetMethod = strpos($targetMethod, $this->convertToMethodName($route));
            if ($routePositionInTargetMethod >= 0) {
                $numberOfSectionsInCoverRoute = count(explode("_", $convertedRoute));
                $targetMethod2 = str_replace("_", ";", $this->request->path);
                $targetMethod2 = $this->convertToMethodName($targetMethod2);
                $targetMethod_exploded = explode("_", $targetMethod2);
                while (--$numberOfSectionsInCoverRoute > 0) {
                    array_shift($targetMethod_exploded);
                }
                $aliasMethod = implode("_", $targetMethod_exploded);
                $aliasMethod = str_replace(";", "_", $aliasMethod);
            }
        }
        if ($this->hasBind() && $this->bind != '') {
            $targetList = [];
        } else {
            $targetList = [
                $className . "::" . $targetMethod . "_" . $this->request->method,
                $className . "::" . $targetMethod . "_" . strtolower($this->request->method),
            ];
            if ($targetMethod !== '') {
                $targetList[] = $className . "::" . $targetMethod;
            }
        }
        if (!in_array($route, ['', '/'])) {
            $targetList[] = $className . "::" . $aliasMethod . "_" . $this->request->method;
            $targetList[] = $className . "::" . $aliasMethod . "_" . strtolower($this->request->method);
        }
        if (!in_array($route, ['', '/']) && $aliasMethod !== '') {
            $targetList[] = $className . "::" . $aliasMethod;
        }
        return $targetList;
    }

    private function convertToMethodName($url)
    {
        $url = $url != '' && $url[0] == '/' ? substr($url, 1) : $url;
        return str_replace(
            "/",
            "_",
            $url
        );
    }

    public function run()
    {
        try {
            if (($result = $this->middleware_run()) !== null) {
                Response::send($result);
            };
            if (!$this->callback) {

                // here we must have http error 404 not found
                trigger_error('no route match!');
                die;
            }
            $callback = $this->callback;
            $response = $this->interceptor_run($callback);
            Response::send($response);
        } catch (RequestException $e) {
            trigger_error($e, E_USER_ERROR);
            die; // tempoprary before filters added
        }
    }

    public function globalMiddleware_add($callable)
    {
        $this->globalMiddlewareList[] = $callable;
    }

    public function use($callable)
    {
        return $this->middleware_add($callable);
    }

    public function middleware($callable)
    {
        return $this->middleware_add($callable);
    }

    public function middleware_add($callable)
    {
        if ($this->skip) {
            return $this;
        }
        $this->middlewareList[] = $callable;
        return $this;
    }

    public function middleware_run()
    {
        $middlewareList = array_merge($this->globalMiddlewareList, $this->middlewareList);
        foreach ($middlewareList as $middleware) {
            $result = $middleware($this->request);
            if ($result instanceof Request) {
                $this->request = $result;
            } else {
                return $result;
            }
        }
        return null;
    }

    public function globalInterceptor_add($callable)
    {
        $this->globalInterceptorList[] = $callable;
    }

    public function interceptor($callable)
    {
        return $this->interceptor_add($callable);
    }

    public function expect(string $class)
    {
        if (!(class_exists($class) &&
            ($object = new $class) instanceof RequestDataObject)) {
            throw new Exception("Error adding expectation of $class", 1);
        }
        $interceptor = function ($request, $next) use ($object) {
            $request = $object->injectData($request);
            return $next($request);
        };
        return $this->interceptor_add($interceptor);
    }

    public function interceptor_add($callable)
    {
        if ($this->skip) {
            return $this;
        }
        $this->interceptorList[] = $callable;
        return $this;
    }

    public function interceptor_run($callback)
    {
        $interceptorList = array_reverse($this->interceptorList);
        $interceptorList = array_merge($interceptorList, array_reverse($this->globalInterceptorList));
        $nextCallable = $callback;
        foreach ($interceptorList as $interceptor) {
            $nextCallable = function ($request) use ($interceptor, $nextCallable) {
                return $interceptor($request, $nextCallable);
            };
        }
        return $nextCallable($this->request);
    }

    private function skip()
    {
        $this->skip = true;
        return $this;
    }
}
