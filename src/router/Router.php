<?php

namespace miladm\router;

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

    function __construct()
    {
        $this->request = new Request();
    }

    public function get($route, $callback)
    {
        if ($this->request->method !== 'GET') {
            return $this->skip();
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
        if (!$this->request->checkIfMatch($route)) {
            return $this->skip();
        }
        $this->register($route, $callback);
        return $this;
    }

    public function any($route, $callback)
    {
        if (!$this->request->checkIfMatch($route)) {
            return $this->skip();
        }
        $this->register($route, $callback);
        return $this;
    }

    private function register($route, $callback)
    {
        if ($this->callback !== null) {
            if (!$this->request->checkIfHashMatch($route)) {
                return;
            }
        }
        $this->skip = false;
        $this->callback = $callback;
    }

    public function run()
    {
        try {
            $this->middleware_run();
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
            }
        }
    }

    public function globalInterceptor_add($callable)
    {
        $this->globalInterceptorList[] = $callable;
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
