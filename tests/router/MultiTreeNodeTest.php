<?php

namespace miladmTest\router;

use miladm\oldRouter\router\Request;
use miladm\router\Controller;
use miladm\router\exceptions\ControllerNotFound;
use miladm\router\exceptions\InvalidControllerType;
use miladm\router\Group;
use miladm\router\interface\Middleware;
use miladm\router\MultiTreeNode;
use miladm\router\RequestMethod;
use miladmTest\router\stubs\ControllerWithMiddleware;
use miladmTest\router\stubs\GroupWithMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
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
            if ($expectation === '') {
                $this->assertArrayNotHasKey($expectation, $this->multiTreeNode->dump(), json_encode($this->multiTreeNode->dump()));
            } else {
                $this->assertArrayHasKey($expectation, $this->multiTreeNode->dump(), json_encode($this->multiTreeNode->dump()));
            }
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

    function testErrorOnBindGroup()
    {
        $this->expectException(InvalidControllerType::class);
        $controllerList = [
            "" => 1
        ];

        /** @var MockObject $group */
        $group = $this->createMock(Group::class);
        $group->method('controllerList')->willReturn($controllerList);

        /** @var Group $group */
        $this->multiTreeNode->bindGroup($group);
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

    function testGetController()
    {

        /** @var MockObject $controller */
        $controller = $this->createMock(Controller::class);
        $controller->method('requestMethod')->willReturn(RequestMethod::GET);

        /** @var MockObject $controller2 */
        $controller2 = $this->createMock(Controller::class);
        $controller2->method('requestMethod')->willReturn(RequestMethod::POST);

        /** @var MockObject */
        $group2 = $this->createMock(Group::class);
        $group2->method('controllerList')->willReturn([
            'action1' => $controller
        ]);

        /** @var MockObject */
        $group3 = $this->createMock(Group::class);
        $group3->method('controllerList')->willReturn([
            'action5' => $controller2,
            'sub' => $group2
        ]);

        /** @var MockObject */
        $group = $this->createMock(Group::class);
        $group->method('controllerList')->willReturn([
            'action1' => $controller,
            'group2' => $group2,
            'so' => $group3
        ]);

        /** @var Group $group */
        $this->multiTreeNode->bindGroup($group);
        $expectationTest = [
            '/action1/' => $controller,
            'action1/' => $controller,
            '/group2/action1/' => $controller,
            'action1' => $controller,
            'group2/action1' => $controller,
            'so/sub/action1' => $controller
        ];
        foreach ($expectationTest as $path => $controllerItem) {
            $this->assertEquals(
                $controllerItem,
                $this->multiTreeNode->getController($path, RequestMethod::GET)
            );
        }
        $this->assertEquals(
            RequestMethod::POST,
            $this->multiTreeNode->getController('so/action5', RequestMethod::POST)->requestMethod()
        );
    }

    function testBindMultipleControllerByGroup()
    {
        /** @var MockObject $controller */
        $controller = $this->createMock(Controller::class);
        $controller->method('requestMethod')->willReturn(RequestMethod::GET);

        /** @var MockObject $controller2 */
        $controller2 = $this->createMock(Controller::class);
        $controller2->method('requestMethod')->willReturn(RequestMethod::POST);

        /** @var MockObject */
        $group = $this->createMock(Group::class);
        $group->method('controllerList')->willReturn([
            'action1' => [
                $controller,
                $controller2
            ]
        ]);

        /** @var Group $group */
        $this->multiTreeNode->bindGroup($group);
        $this->assertEquals(
            RequestMethod::POST,
            $this->multiTreeNode->getController('action1', RequestMethod::POST)->requestMethod()
        );
        $this->assertEquals(
            RequestMethod::GET,
            $this->multiTreeNode->getController('action1', RequestMethod::GET)->requestMethod()
        );
    }

    private function setSampleForControllerNotFound()
    {
        /** @var MockObject $controller */
        $controller = $this->createMock(Controller::class);
        $controller->method('requestMethod')->willReturn(RequestMethod::GET);

        /** @var MockObject */
        $group = $this->createMock(Group::class);
        $group->method('controllerList')->willReturn([
            'action1' => $controller
        ]);

        /** @var Group $group */
        $this->multiTreeNode->bindGroup($group);
    }

    function testControllerNotFound_basicTest()
    {
        $this->expectException(ControllerNotFound::class);
        $this->setSampleForControllerNotFound();
        $this->multiTreeNode->getController('', RequestMethod::GET);
    }

    function testControllerNotFound_requestMethodTest()
    {
        $this->expectException(ControllerNotFound::class);
        $this->setSampleForControllerNotFound();
        $this->multiTreeNode->getController('action1', RequestMethod::POST);
    }

    function testControllerNotFound_subPathNotFound()
    {
        $this->expectException(ControllerNotFound::class);
        $this->setSampleForControllerNotFound();
        $this->multiTreeNode->getController('action1/something', RequestMethod::GET);
    }

    function testOverrideAndMergeGroups()
    {
        /** @var MockObject $controller */
        $controller = $this->createMock(Controller::class);
        $controller->method('requestMethod')->willReturn(RequestMethod::GET);

        /** @var MockObject */
        $mw = $this->createMock(Middleware::class);
        $mw->method('handler')->willReturn('hello');

        /** @var MockObject */
        $mw2 = $this->createMock(Middleware::class);
        $mw2->method('handler')->willReturn('bye');

        /** @var MockObject */
        $group = $this->createMock(GroupWithMiddleware::class);
        $group->method('controllerList')->willReturn([
            'action1' => $controller
        ]);
        $group->method('middlewareList')->willReturn([
            $mw
        ]);


        /** @var Group $group */
        $this->multiTreeNode->bindGroup($group);

        /** @var MockObject */
        $group = $this->createMock(GroupWithMiddleware::class);
        $group->method('controllerList')->willReturn([
            'action2' => $controller
        ]);
        $group->method('middlewareList')->willReturn([
            $mw2
        ]);

        /** @var Group $group */
        $this->multiTreeNode->bindGroup($group);
        $tree = $this->multiTreeNode->dump();

        $otherMultiNode = new MultiTreeNode;

        /** @var MockObject */
        $group2 = $this->createMock(GroupWithMiddleware::class);
        $group2->method('controllerList')->willReturn([
            'action1' => $controller,
            'action2' => $controller
        ]);
        $group2->method('middlewareList')->willReturn([
            $mw,
            $mw2
        ]);

        /** @var Group $group2 */
        $otherMultiNode->bindGroup($group2);
        $tree2 = $otherMultiNode->dump();
        $this->assertEquals($tree, $tree2, json_encode([$tree, $tree2]));

        $request = $this->createMock(Request::class);
        $next = fn () => null;
        $this->assertEquals($tree['_middlewareList'][0]->handler($request, $next), $tree2['_middlewareList'][0]->handler($request, $next));
        $this->assertEquals($tree['_middlewareList'][1]->handler($request, $next), $tree2['_middlewareList'][1]->handler($request, $next));
    }

    function testRegisterControllerWithDeepPath_direct()
    {
        /** @var MockObject $controller */
        $controller = $this->createMock(Controller::class);
        $controller->method('requestMethod')->willReturn(RequestMethod::GET);
        $path = '/some/path/deep/inside';

        /** @var Controller $controller */
        $this->multiTreeNode->registerController($path, $controller);

        $tree = $this->multiTreeNode->dump();
        $this->assertArrayHasKey('inside', $tree['some']['path']['deep']);
        $this->assertEquals($controller, $this->multiTreeNode->getController($path, RequestMethod::GET));
    }

    function testRegisterControllerWithDeepPath_fromBindGroup()
    {
        /** @var MockObject $controller */
        $controller = $this->createMock(Controller::class);
        $controller->method('requestMethod')->willReturn(RequestMethod::GET);
        $path = '/some/path/deep/inside';

        /** @var MockObject */
        $group = $this->createMock(Group::class);
        $group->method('controllerList')->willReturn([
            $path => $controller
        ]);

        /** @var Group $group */
        $this->multiTreeNode->bindGroup($group);
        $tree = $this->multiTreeNode->dump();
        $this->assertArrayHasKey('inside', $tree['some']['path']['deep']);
        $this->assertEquals($controller, $this->multiTreeNode->getController($path, RequestMethod::GET));
    }

    function testRegisterControllerWithDeepPath_fromRegisterSubGroup()
    {
        /** @var MockObject $controller */
        $controller = $this->createMock(Controller::class);
        $controller->method('requestMethod')->willReturn(RequestMethod::GET);
        $path = '/some/path/deep/inside';

        /** @var MockObject */
        $group = $this->createMock(Group::class);
        $group->method('controllerList')->willReturn([
            $path => $controller
        ]);

        /** @var Group $group */
        $this->multiTreeNode->registerSubGroup("", $group);
        $this->multiTreeNode->registerSubGroup("/baraba", $group);
        $this->multiTreeNode->registerSubGroup("here/sub/group", $group);
        $tree = $this->multiTreeNode->dump();
        $this->assertArrayHasKey('inside', $tree["baraba"]['some']['path']['deep']);
        $this->assertArrayHasKey('group', $tree['here']['sub']);
        $this->assertEquals($controller, $this->multiTreeNode->getController($path, RequestMethod::GET));
        $this->assertEquals($controller, $this->multiTreeNode->getController("baraba/" . $path, RequestMethod::GET));
    }

    function testRegisterControllerWithDynamicParam()
    {
        /** @var MockObject $controller */
        $controller = $this->createMock(Controller::class);
        $controller->method('requestMethod')->willReturn(RequestMethod::GET);
        $path = '/somepath/:dynamicParameter';

        /** @var MockObject */
        $group = $this->createMock(Group::class);
        $group->method('controllerList')->willReturn([
            $path => $controller
        ]);

        /** @var Group $group */
        $this->multiTreeNode->bindGroup($group);
        $tree = $this->multiTreeNode->dump();
        $this->assertArrayHasKey(':dynamicParameter', $tree['somepath']['_dynamicPathNodeList']);
        $this->assertEquals($controller, $this->multiTreeNode->getController("/somepath/somedata", RequestMethod::GET));
        $this->assertEquals($controller, $this->multiTreeNode->getController("/somepath/other", RequestMethod::GET));
        $this->assertEquals($controller, $this->multiTreeNode->getController("/somepath/data", RequestMethod::GET));
    }

    // check support both override and dynamic url

    // check support for group dynamic 
    function testRegisterSubGroupWithDynamicParam()
    {
        /** @var MockObject $controller */
        $controller = $this->createMock(Controller::class);
        $controller->method('requestMethod')->willReturn(RequestMethod::GET);
        $path = '/somepath/:dynamicParameter';

        /** @var MockObject */
        $group = $this->createMock(Group::class);
        $group->method('controllerList')->willReturn([
            $path => $controller
        ]);

        /** @var Group $group */
        $this->multiTreeNode->registerSubGroup("/group/:item", $group);
        $tree = $this->multiTreeNode->dump();
        $this->assertArrayHasKey(':item', $tree['group']['_dynamicPathNodeList']);
        $this->assertArrayHasKey(
            ':dynamicParameter',
            $tree['group']['_dynamicPathNodeList'][':item']['somepath']['_dynamicPathNodeList']
        );
        $this->assertEquals(
            $controller,
            $this->multiTreeNode->getController("/group/1234/somepath/somedata", RequestMethod::GET)
        );
        $this->assertEquals(
            $controller,
            $this->multiTreeNode->getController("/group/othertype/somepath/other", RequestMethod::GET)
        );
        $this->assertEquals(
            $controller,
            $this->multiTreeNode->getController("group/somethingelse/somepath/data", RequestMethod::GET)
        );
        $this->expectException(ControllerNotFound::class);
        $this->assertEquals(
            $controller,
            $this->multiTreeNode->getController("group/somepath/data", RequestMethod::GET)
        );
    }

    // one path with multiple dynamic parameter 

    // dynamic routes add a middleware to enter the dynamic parameter of the url in the request object
}