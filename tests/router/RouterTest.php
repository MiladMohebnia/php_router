<?php

namespace miladmTest\router;

use miladm\router\Router;
use miladm\router\Group;
use miladm\router\Controller;
use miladm\router\RequestMethod;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;


class SomeClass
{
    public function doSomething()
    {
        // Do something.
    }
}

class RouterTest extends TestCase
{
    public function testGroupRegistration()
    {
        $group = $this->createMock(Group::class);
        $path = "/testGroup";

        Router::group($path, $group);

        $tree = Router::dump();
        $this->assertArrayHasKey($path, $tree);
    }

    public function testControllerRegistration()
    {
        /** @var MockObject&Controller $controller */
        $controller = $this->createMock(Controller::class);
        $controller->method('requestMethod')->willReturn(RequestMethod::GET);
        $path = "/anything";

        Router::controller($path, $controller);

        $tree = Router::dump();
        $this->assertArrayHasKey($path, $tree);
    }
}
