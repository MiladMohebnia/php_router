<?php

declare(strict_types=1);

namespace miladmTest;

use miladm;
use miladm\Group;
use miladm\Controller;
use miladm\exceptions\ControllerNotFound;
use miladm\interface\Middleware;
use miladm\Request;
use miladm\RequestMethod;
use miladmTest\stubs\ControllerWithMiddleware;
use miladmTest\stubs\GroupWithMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{

    protected function setUp(): void
    {
        $_POST = [];
        $_SERVER = [];
        $_FILES = [];
        Router::reset();
    }

    private function createMiddleware(string|int $handlerResponse = 0): Middleware
    {

        /** @var MockObject $mw */
        $mw = $this->createMock(Middleware::class);
        $mw->method('handler')->willReturn($handlerResponse);
        /** @var Middleware $mw */
        return $mw;
    }

    private function createRealMiddleware(callable $handler): Middleware
    {
        $mw = new class($handler) implements Middleware
        {
            private $overrideHandler;

            function __construct(callable $overrideHandler)
            {
                $this->overrideHandler = $overrideHandler;
            }

            function handler(Request $request, callable $next)
            {
                $callable = $this->overrideHandler;
                return $callable($request, $next);
            }
        };
        return $mw;
    }

    private function createController(
        string|int $handlerResponse = 0,
        RequestMethod $requestMethod = RequestMethod::GET
    ): Controller {

        /** @var MockObject $controller */
        $controller = $this->createMock(Controller::class);
        $controller->method('requestMethod')->willReturn($requestMethod);
        $controller->method('handler')->willReturn($handlerResponse);

        /** @var Controller $controller */
        return $controller;
    }

    private function createControllerWithMiddleware(
        string|int $handlerResponse = 0,
        array $middlewareList = [],
        RequestMethod $requestMethod = RequestMethod::GET
    ): ControllerWithMiddleware {

        /** @var MockObject $controller */
        $controller = $this->createMock(ControllerWithMiddleware::class);
        $controller->method('requestMethod')->willReturn($requestMethod);
        $controller->method('middlewareList')->willReturn($middlewareList);
        $controller->method('handler')->willReturn($handlerResponse);

        /** @var ControllerWithMiddleware $controller */
        return $controller;
    }

    private function createGroup(array $controllerList = []): Group
    {

        /** @var MockObject $group */
        $group = $this->createMock(Group::class);
        $group->method('controllerList')->willReturn($controllerList);

        /** @var Group $group */
        return $group;
    }

    private function createGroupWithMiddleware(array $controllerList = [], array $middlewareList): GroupWithMiddleware
    {

        /** @var MockObject $group */
        $group = $this->createMock(GroupWithMiddleware::class);

        $group->method('controllerList')->willReturn($controllerList);
        $group->method('middlewareList')->willReturn($middlewareList);

        /** @var GroupWithMiddleware $group */
        return $group;
    }


    public function testGroupRegistration()
    {
        /** @var MockObject&Group $group */
        $group = $this->createMock(Group::class);
        $path = "/testGroup";
        Router::group($path, $group);
        $tree = Router::dump();
        $this->assertArrayHasKey("testGroup", $tree);
    }

    public function testControllerRegistration()
    {
        $controller = $this->createController(0);
        $path = "/anything";
        Router::controller($path, $controller);
        $tree = Router::dump();
        $this->assertArrayHasKey("anything", $tree);
    }

    public function testSubGroupRegistration()
    {
        $controller = $this->createController(0);

        /** @var MockObject&Group $group1 */
        $group2 = $this->createGroup([]);
        $group1 = $this->createGroup([
            "/otherGroup" => $group2,
            "/controller" => $controller
        ]);
        $path = "/parentGroup";
        Router::group("parentGroup", $group1);
        $tree = Router::dump();
        $this->assertArrayHasKey("parentGroup", $tree);
        $this->assertArrayHasKey("otherGroup", $tree["parentGroup"]);
        $this->assertArrayHasKey("controller", $tree["parentGroup"]);
    }

    public function testGlobalMiddleware()
    {
        $group1 = $this->createGroup([]);
        $path = "/sampleGroupForMiddleware";
        Router::group($path, $group1);
        $mw = $this->createMiddleware();
        Router::middleware($mw);
        $tree = Router::dump();
        $this->assertEquals($tree["_middlewareList"], [$mw]);
    }

    public function testGlobalMiddlewareOrder()
    {
        $group1 = $this->createGroup();
        $path = "/sampleGroupForMiddleware";
        Router::group($path, $group1);
        $mw = $this->createMiddleware();
        $mw2 = $this->createMiddleware();
        Router::middleware($mw2);
        Router::middleware($mw);
        $tree = Router::dump();
        $this->assertEquals($tree["_middlewareList"], [$mw2, $mw]);
    }


    public function testGroupMiddleware()
    {
        $middleware = $this->createMiddleware();
        $controller = $this->createController(0);
        $group = $this->createGroupWithMiddleware(
            ["/index" => $controller],
            [$middleware]
        );
        Router::group("/a", $group);
        $tree = Router::dump();
        $this->assertContains($middleware, $tree["a"]["_middlewareList"]);
    }

    public function testGroupControllerMiddleware()
    {
        $middleware = $this->createMiddleware();
        $controller = $this->createControllerWithMiddleware(0, [$middleware]);
        $group = $this->createGroup([
            "/index" => $controller
        ]);
        Router::group("/a", $group);
        $tree = Router::dump();
        $response = $tree["a"]["_middlewareList"];
        $this->assertEquals([], $response, json_encode($response));
        $this->assertEquals($controller, $tree["a"]["_controller"][$controller->requestMethod()->value]);
    }

    public function testRun()
    {
        $outputString = 'hello world';
        $controller = $this->createController($outputString);
        $group = $this->createGroup(['test' => $controller]);

        $_SERVER['REQUEST_URI'] = "/a/test?some=query";

        $router = new Router;
        $router::group('a', $group);

        ob_start();
        $router::run();
        $result = ob_get_clean();
        $this->assertEquals($outputString, $result);
    }

    public function testRunWithOneMiddleware()
    {
        $middlewareString = 'running mw 1';
        $outputString = 'hello world';
        $middleware = $this->createMiddleware($middlewareString);
        $controller = $this->createControllerWithMiddleware($outputString, [$middleware]);
        $group = $this->createGroup(['test' => $controller]);

        $_SERVER['REQUEST_URI'] = "/a/test?some=query";

        $router = new Router;
        $router::group('a', $group);

        ob_start();
        $router::run();
        $result = ob_get_clean();
        $this->assertEquals($middlewareString, $result);
    }

    public function testRunWithMultiMiddlewareOrder()
    {
        $firstMiddlewareString = 'running mw 1';
        $middleware1 = $this->createMiddleware($firstMiddlewareString);
        $middleware2 = $this->createMiddleware('running mw2');
        $controller = $this->createControllerWithMiddleware('hello world', [$middleware1, $middleware2]);
        $group = $this->createGroup(['test' => $controller]);

        $_SERVER['REQUEST_URI'] = "/a/test?some=query";

        $router = new Router;
        $router::group('a', $group);

        ob_start();
        $router::run();
        $result = ob_get_clean();
        $this->assertEquals($firstMiddlewareString, $result);
    }

    public function testRunGroupMiddlewareFirst()
    {
        $firstMiddlewareString = 'running mw 1';
        $middleware1 = $this->createMiddleware($firstMiddlewareString);
        $middleware2 = $this->createMiddleware('running mw2');
        $controller = $this->createControllerWithMiddleware('hello world', [$middleware2]);
        $group = $this->createGroupWithMiddleware(['test' => $controller], [$middleware1]);

        $_SERVER['REQUEST_URI'] = "/a/test?some=query";

        $router = new Router;
        $router::group('a', $group);

        ob_start();
        $router::run();
        $result = ob_get_clean();
        $this->assertEquals($firstMiddlewareString, $result);
    }

    public function testRunMiddlewareModifyRequest()
    {
        $middleware1 = $this->createRealMiddleware(function (Request $request, $next) {
            $request->attach(['sampleData' => 'sampleValue']);
            return $next($request);
        });

        $middleware2 = $this->createRealMiddleware(function (Request $request, $next) {
            return $request->attachment->sampleData;
        });
        $controller = $this->createControllerWithMiddleware('hello world', [$middleware2]);
        $group = $this->createGroupWithMiddleware(['test' => $controller], [$middleware1]);

        $_SERVER['REQUEST_URI'] = "/a/test?some=query";

        $router = new Router;
        $router::group('a', $group);

        ob_start();
        $router::run();
        $result = ob_get_clean();
        $this->assertEquals('sampleValue', $result);
    }

    public function testRunMiddlewareModifyRequestDetectInController()
    {
        $middleware1 = $this->createRealMiddleware(function (Request $request, $next) {
            $request->attach(['sampleData' => 'sampleValue']);
            return $next($request);
        });

        $controller = new class extends Controller
        {
            function handler(Request $request): string|int|array
            {
                return $request->attachment->sampleData;
            }
        };
        $group = $this->createGroupWithMiddleware(['test' => $controller], [$middleware1]);

        $_SERVER['REQUEST_URI'] = "/a/test?some=query";

        $router = new Router;
        $router::group('a', $group);

        ob_start();
        $router::run();
        $result = ob_get_clean();
        $this->assertEquals('sampleValue', $result);
    }

    // public function testControllerNotFound()
    // {
    //     $router = new Router;

    //     ob_start();
    //     @$router::run();
    //     $result = ob_get_clean();

    //     $exception = new ControllerNotFound("");
    //     $this->assertEquals(
    //         json_encode($exception->showErrorPage()),
    //         $result
    //     );
    // }
}
