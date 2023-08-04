<?php

namespace miladmTest\router;

use miladm\router\Controller;
use miladm\router\Group;
use miladm\router\interface\Middleware;
use miladm\router\MultiTreeNode;
use miladm\router\RequestMethod;
use miladmTest\router\stubs\ControllerWithMiddleware;
use PHPUnit\Framework\TestCase;

class MultiTreeNodeTest extends TestCase
{

    private MultiTreeNode $multiTreeNode;

    private const pathList = [
        '/' => '',
        '/index' => '',
        'index' => '',
        '' => '',
        '/index-other' => 'index-other',
        'index-other' => 'index-other',
        'index/' => '',
        '/index/' => '',
        'index-other/' => 'index-other',
        '/index-other/' => 'index-other',
    ];

    protected function setUp(): void
    {
        $this->multiTreeNode = new MultiTreeNode;
    }

    function testPathNames_controller()
    {

        /** @var MockObject $controller */
        $controller = $this->createMock(Controller::class);
        $controller->method('requestMethod')->willReturn(RequestMethod::GET);
        foreach (self::pathList as $path => $expectation) {
            $this->multiTreeNode = new MultiTreeNode;
            /** @var Controller $controller */
            $this->multiTreeNode->registerController($path, $controller);
            $this->assertArrayHasKey($expectation, $this->multiTreeNode->dump(), json_encode($this->multiTreeNode->dump()));
        }
    }

    function testPathNames_group()
    {

        /** @var MockObject $group */
        $group = $this->createMock(Group::class);
        foreach (self::pathList as $path => $expectation) {
            $this->multiTreeNode = new MultiTreeNode;
            /** @var Group $group */
            $this->multiTreeNode->registerSubGroup($path, $group);
            $this->assertArrayHasKey($expectation, $this->multiTreeNode->dump(), json_encode($this->multiTreeNode->dump()));
        }
    }

    function testPathNames_bindGroup()
    {
        $controllerList = [];

        /** @var MockObject $controller */
        $controller = $this->createMock(Controller::class);
        $controller->method('requestMethod')->willReturn(RequestMethod::GET);
        foreach (self::pathList as $path => $expectation) {
            $controllerList[$path] = $controller;
        }
        /** @var MockObject $group */
        $group = $this->createMock(Group::class);
        $group->method('controllerList')->willReturn($controllerList);

        /** @var Group $group */
        $this->multiTreeNode->bindGroup($group);
        foreach (self::pathList as $path => $expectation) {
            $this->assertArrayHasKey($expectation, $this->multiTreeNode->dump(), json_encode($this->multiTreeNode->dump()));
        }
    }

    function testMiddleware()
    {
        $mw = $this->createMock(Middleware::class);

        /** @var MockObject $controller */
        $controller = $this->createMock(ControllerWithMiddleware::class);
        $controller->method('requestMethod')->willReturn(RequestMethod::GET);
        $controller->method('middlewareList')->willReturn([$mw]);

        /** @var ControllerWithMiddleware $controller */
        $this->multiTreeNode->registerController('/', $controller);
        $this->multiTreeNode->addMiddleware($mw);
        $tree = $this->multiTreeNode->dump();
        // $this->assertEquals([$mw], $tree['']['_middlewareList'], json_encode($tree['']['_middlewareList']));
        $this->assertEquals([$mw], $tree['_middlewareList'], json_encode($tree['_middlewareList']));
    }

    function testBindController()
    {

        /** @var MockObject $controller */
        $controller = $this->createMock(Controller::class);
        $controller->method('requestMethod')->willReturn(RequestMethod::GET);

        /** @var Controller $controller */
        $this->multiTreeNode->registerController('/', $controller);

        /** @var MockObject $controller2 */
        $controller2 = $this->createMock(Controller::class);
        $controller2->method('requestMethod')->willReturn(RequestMethod::POST);

        /** @var Controller $controller2 */
        $this->multiTreeNode->registerController('/', $controller2);
        $tree = $this->multiTreeNode->dump();
        $this->assertEquals([
            RequestMethod::GET->value => $controller,
            RequestMethod::POST->value => $controller
        ], $tree['']['_controller'], json_encode($tree['']['_controller']));
    }
}
