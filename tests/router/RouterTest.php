<?php

namespace miladmTest\router;

use miladm\router\Router;
use miladm\router\Group;
use miladm\router\Controller;
use miladm\router\interface\Middleware;
use miladm\router\RequestMethod;
use miladmTest\router\stubs\ControllerWithMiddleware;
use miladmTest\router\stubs\GroupWithMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{

    protected function setUp(): void
    {
        Router::reset();
    }

    public function testGroupRegistration()
    {
        $group = $this->createMock(Group::class);
        $path = "/testGroup";
        Router::group($path, $group);
        $tree = Router::dump();
        $this->assertArrayHasKey("testGroup", $tree);
    }

    public function testControllerRegistration()
    {
        /** @var MockObject&Controller $controller */
        $controller = $this->createMock(Controller::class);
        $controller->method('requestMethod')->willReturn(RequestMethod::GET);
        $path = "/anything";
        Router::controller($path, $controller);
        $tree = Router::dump();
        $this->assertArrayHasKey("anything", $tree);
    }

    public function testSubGroupRegistration()
    {
        /** @var MockObject&Controller $controller */
        $controller = $this->createMock(Controller::class);
        $controller->method('requestMethod')->willReturn(RequestMethod::GET);

        /** @var MockObject&Group $group1 */
        $group1 = $this->createMock(Group::class);
        $group2 = $this->createMock(Group::class);
        $group1->method('controllerList')->willReturn([
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
        /** @var MockObject&Group $group1 */
        $group1 = $this->createMock(Group::class);
        $path = "/sampleGroupForMiddleware";
        Router::group($path, $group1);
        $mw = $this->createMock(Middleware::class);
        Router::middleware($mw);
        $tree = Router::dump();
        $this->assertEquals($tree["_middlewareList"], [$mw]);
    }

    public function testGlobalMiddlewareOrder()
    {
        /** @var MockObject&Group $group1 */
        $group1 = $this->createMock(Group::class);
        $path = "/sampleGroupForMiddleware";
        Router::group($path, $group1);
        $mw = $this->createMock(Middleware::class);
        $mw2 = $this->createMock(Middleware::class);
        Router::middleware($mw2);
        Router::middleware($mw);
        $tree = Router::dump();
        $this->assertEquals($tree["_middlewareList"], [$mw2, $mw]);
    }


    public function testGroupMiddleware()
    {
        $middleware = $this->createMock(Middleware::class);

        /** @var MockObject&Controller $controller */
        $controller = $this->createMock(Controller::class);
        $controller->method('requestMethod')->willReturn(RequestMethod::GET);

        /** @var MockObject&Group $group */
        $group = $this->createMock(GroupWithMiddleware::class);
        $group->method('middlewareList')->willReturn([$middleware]);
        $group->method('controllerList')->willReturn([
            "/index" => $controller
        ]);
        Router::group("/a", $group);
        $tree = Router::dump();
        $this->assertContains($middleware, $tree["a"]["_middlewareList"]);
    }

    public function testGroupControllerMiddleware()
    {
        $middleware = $this->createMock(Middleware::class);

        /** @var MockObject&Controller $controller */
        $controller = $this->createMock(ControllerWithMiddleware::class);
        $controller->method('requestMethod')->willReturn(RequestMethod::GET);
        $controller->method('middlewareList')->willReturn([$middleware]);


        /** @var MockObject&Group $group */
        $group = $this->createMock(GroupWithMiddleware::class);
        $group->method('controllerList')->willReturn([
            "/index" => $controller
        ]);
        Router::group("/a", $group);
        $tree = Router::dump();
        $this->assertContains($middleware, $tree["a"][""]["_middlewareList"]);
    }
}
