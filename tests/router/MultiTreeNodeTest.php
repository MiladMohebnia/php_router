<?php

namespace miladmTest\router;

use miladm\router\Request;
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

    private const PATH_LIST = [
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

    private function createMiddleware(string|int $handlerResponse = 0): Middleware
    {

        /** @var MockObject $mw */
        $mw = $this->createMock(Middleware::class);
        $mw->method('handler')->willReturn($handlerResponse);
        /** @var Middleware $mw */
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

    function testPathNames_controller()
    {

        $controller = $this->createController();
        foreach (self::PATH_LIST as $path => $expectation) {
            $this->multiTreeNode = new MultiTreeNode;
            $this->multiTreeNode->registerController($path, $controller);
            $this->assertArrayHasKey($expectation, $this->multiTreeNode->dump(), json_encode($this->multiTreeNode->dump()));
        }
    }

    function testPathNames_group()
    {
        $group = $this->createGroup();
        foreach (self::PATH_LIST as $path => $expectation) {
            $this->multiTreeNode = new MultiTreeNode;

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

        $controller = $this->createController();
        foreach (self::PATH_LIST as $path => $expectation) {
            $controllerList[$path] = $controller;
        }
        $group = $this->createGroup($controllerList);

        $this->multiTreeNode->bindGroup($group);
        foreach (self::PATH_LIST as $path => $expectation) {
            $this->assertArrayHasKey($expectation, $this->multiTreeNode->dump(), json_encode($this->multiTreeNode->dump()));
        }
    }

    function testErrorOnBindGroup()
    {
        $this->expectException(InvalidControllerType::class);
        $controllerList = ["" => 1];
        $group = $this->createGroup($controllerList);
        $this->multiTreeNode->bindGroup($group);
    }

    function testMiddleware()
    {
        $mw = $this->createMiddleware();
        $controller = $this->createControllerWithMiddleware(0, [$mw]);
        $this->multiTreeNode->registerController('/', $controller);
        $this->multiTreeNode->addMiddleware($mw);
        $tree = $this->multiTreeNode->dump();
        $this->assertEquals([$mw], $tree['_middlewareList'], json_encode($tree['_middlewareList']));
    }

    function testBindController()
    {

        $controller = $this->createController();
        $this->multiTreeNode->registerController('/', $controller);

        $controller2 = $this->createController(0, RequestMethod::POST);
        $this->multiTreeNode->registerController('/', $controller2);
        $tree = $this->multiTreeNode->dump();
        $this->assertEquals([
            RequestMethod::GET->value => $controller,
            RequestMethod::POST->value => $controller
        ], $tree['']['_controller'], json_encode($tree['']['_controller']));
    }

    function testGetController()
    {

        $controller = $this->createController();

        $controller2 = $this->createController(0, RequestMethod::POST);

        $group2 = $this->createGroup([
            'action1' => $controller
        ]);

        $group3 = $this->createGroup([
            'action5' => $controller2,
            'sub' => $group2
        ]);

        $group = $this->createGroup([
            'action1' => $controller,
            'group2' => $group2,
            'so' => $group3
        ]);

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
                $this->multiTreeNode->findController($path, RequestMethod::GET)
            );
        }
        $this->assertEquals(
            RequestMethod::POST,
            $this->multiTreeNode->findController('so/action5', RequestMethod::POST)->requestMethod()
        );
    }

    function testBindMultipleControllerByGroup()
    {
        $controller = $this->createController();

        $controller2 = $this->createController(0, RequestMethod::POST);

        $group = $this->createGroup([
            'action1' => [
                $controller,
                $controller2
            ]
        ]);

        $this->multiTreeNode->bindGroup($group);
        $this->assertEquals(
            RequestMethod::POST,
            $this->multiTreeNode->findController('action1', RequestMethod::POST)->requestMethod()
        );
        $this->assertEquals(
            RequestMethod::GET,
            $this->multiTreeNode->findController('action1', RequestMethod::GET)->requestMethod()
        );
    }

    private function setSampleForControllerNotFound()
    {
        $controller = $this->createController();

        $group = $this->createGroup([
            'action1' => $controller
        ]);

        $this->multiTreeNode->bindGroup($group);
    }

    function testControllerNotFound_basicTest()
    {
        $this->expectException(ControllerNotFound::class);
        $this->setSampleForControllerNotFound();
        $this->multiTreeNode->findController('', RequestMethod::GET);
    }

    function testControllerNotFound_requestMethodTest()
    {
        $this->expectException(ControllerNotFound::class);
        $this->setSampleForControllerNotFound();
        $this->multiTreeNode->findController('action1', RequestMethod::POST);
    }

    function testControllerNotFound_subPathNotFound()
    {
        $this->expectException(ControllerNotFound::class);
        $this->setSampleForControllerNotFound();
        $this->multiTreeNode->findController('action1/something', RequestMethod::GET);
    }

    function testOverrideAndMergeGroups()
    {
        $request = $this->createMock(Request::class);
        $next = fn () => null;

        $controller = $this->createController();

        $mw = $this->createMiddleware('hello');
        $mw2 = $this->createMiddleware('bye');

        $group = $this->createGroupWithMiddleware([
            'action1' => $controller
        ], [
            $mw
        ]);

        $this->multiTreeNode->bindGroup($group);

        $group1 = $this->createGroupWithMiddleware([
            'action2' => $controller
        ], [
            $mw2
        ]);

        $this->multiTreeNode->bindGroup($group1);
        $tree = $this->multiTreeNode->dump();
        $otherMultiNode = new MultiTreeNode;

        $group2 = $this->createGroupWithMiddleware([
            'action1' => $controller,
            'action2' => $controller
        ], [
            $mw,
            $mw2
        ]);

        $otherMultiNode->bindGroup($group2);
        $tree2 = $otherMultiNode->dump();
        $this->assertEquals($tree, $tree2, json_encode([$tree, $tree2]));

        $this->assertEquals($tree['_middlewareList'][0]->handler($request, $next), $tree2['_middlewareList'][0]->handler($request, $next));
        $this->assertEquals($tree['_middlewareList'][1]->handler($request, $next), $tree2['_middlewareList'][1]->handler($request, $next));
    }

    function testRegisterControllerWithDeepPath_direct()
    {
        $controller = $this->createController();
        $path = '/some/path/deep/inside';
        $this->multiTreeNode->registerController($path, $controller);

        $tree = $this->multiTreeNode->dump();
        $this->assertArrayHasKey('inside', $tree['some']['path']['deep']);
        $this->assertEquals($controller, $this->multiTreeNode->findController($path, RequestMethod::GET));
    }

    function testRegisterControllerWithDeepPath_fromBindGroup()
    {
        $controller = $this->createController();
        $path = '/some/path/deep/inside';

        $group = $this->createGroup([
            $path => $controller
        ]);

        $this->multiTreeNode->bindGroup($group);
        $tree = $this->multiTreeNode->dump();
        $this->assertArrayHasKey('inside', $tree['some']['path']['deep']);
        $this->assertEquals($controller, $this->multiTreeNode->findController($path, RequestMethod::GET));
    }

    function testRegisterControllerWithDeepPath_fromRegisterSubGroup()
    {
        $controller = $this->createController();
        $path = '/some/path/deep/inside';

        $group = $this->createGroup([
            $path => $controller
        ]);

        $this->multiTreeNode->registerSubGroup("", $group);
        $this->multiTreeNode->registerSubGroup("/baraba", $group);
        $this->multiTreeNode->registerSubGroup("here/sub/group", $group);
        $tree = $this->multiTreeNode->dump();
        $this->assertArrayHasKey('inside', $tree["baraba"]['some']['path']['deep']);
        $this->assertArrayHasKey('group', $tree['here']['sub']);
        $this->assertEquals($controller, $this->multiTreeNode->findController($path, RequestMethod::GET));
        $this->assertEquals($controller, $this->multiTreeNode->findController("baraba/" . $path, RequestMethod::GET));
    }

    function testRegisterControllerWithDynamicParam()
    {
        $controller = $this->createController();
        $path = '/somepath/:dynamicParameter';

        $group = $this->createGroup([
            $path => $controller
        ]);

        $this->multiTreeNode->bindGroup($group);
        $tree = $this->multiTreeNode->dump();
        $this->assertArrayHasKey(':dynamicParameter', $tree['somepath']['_dynamicPathNodeList']);
        $this->assertEquals($controller, $this->multiTreeNode->findController("/somepath/somedata", RequestMethod::GET));
        $this->assertEquals($controller, $this->multiTreeNode->findController("/somepath/other", RequestMethod::GET));
        $this->assertEquals($controller, $this->multiTreeNode->findController("/somepath/data", RequestMethod::GET));
    }

    function testSupportBothOverrideAndDynamicUrl()
    {
        $controller = $this->createController(1);
        $controller1 = $this->createController(2);

        $request = $this->createMock(Request::class);

        $this->multiTreeNode->registerController('/dir/other', $controller);
        $this->multiTreeNode->registerController('/dir/:id', $controller1);
        $this->assertEquals(
            $controller->handler($request),
            $this->multiTreeNode->findController('/dir/other', RequestMethod::GET)->handler($request)
        );
        $this->assertEquals(
            $controller1->handler($request),
            $this->multiTreeNode->findController('/dir/other1', RequestMethod::GET)->handler($request)
        );
        $this->assertEquals(
            $controller1->handler($request),
            $this->multiTreeNode->findController('/dir/123', RequestMethod::GET)->handler($request)
        );
    }

    function testRegisterSubGroupWithDynamicParam()
    {
        $controller = $this->createController();
        $path = '/somepath/:dynamicParameter';

        $group = $this->createGroup([
            $path => $controller
        ]);

        $this->multiTreeNode->registerSubGroup("/group/:item", $group);
        $tree = $this->multiTreeNode->dump();
        $this->assertArrayHasKey(':item', $tree['group']['_dynamicPathNodeList']);
        $this->assertArrayHasKey(
            ':dynamicParameter',
            $tree['group']['_dynamicPathNodeList'][':item']['somepath']['_dynamicPathNodeList']
        );
        $this->assertEquals(
            $controller,
            $this->multiTreeNode->findController("/group/1234/somepath/somedata", RequestMethod::GET)
        );
        $this->assertEquals(
            $controller,
            $this->multiTreeNode->findController("/group/othertype/somepath/other", RequestMethod::GET)
        );
        $this->assertEquals(
            $controller,
            $this->multiTreeNode->findController("group/somethingelse/somepath/data", RequestMethod::GET)
        );
        $this->expectException(ControllerNotFound::class);
        $this->assertEquals(
            $controller,
            $this->multiTreeNode->findController("group/somepath/data", RequestMethod::GET)
        );
    }

    function testMultipleDynamicParameters()
    {
        $controller = $this->createController(1);
        $controller1 = $this->createController(2);
        $controller2 = $this->createController(3);

        $request = $this->createMock(Request::class);

        $this->multiTreeNode->registerController('/dir/:other/option1', $controller);
        $this->multiTreeNode->registerController('/dir/:id/option2', $controller1);
        $this->multiTreeNode->registerController('/dir/:anythingelse', $controller2);
        $this->assertEquals(
            $controller->handler($request),
            $this->multiTreeNode->findController('/dir/something/option1', RequestMethod::GET)->handler($request)
        );
        $this->assertEquals(
            $controller1->handler($request),
            $this->multiTreeNode->findController('/dir/something/option2', RequestMethod::GET)->handler($request)
        );
        $this->assertEquals(
            $controller2->handler($request),
            $this->multiTreeNode->findController('/dir/123', RequestMethod::GET)->handler($request)
        );
    }

    function testUpdateDynamicPath()
    {
        $request = $this->createMock(Request::class);

        $controller = $this->createController(1);
        $controller1 = $this->createController(2);

        $this->multiTreeNode->registerController('/dir/:id/option1', $controller);
        $this->multiTreeNode->registerController('/dir/:id/option2', $controller1);

        $this->assertEquals(
            $controller->handler($request),
            $this->multiTreeNode->findController('/dir/something/option1', RequestMethod::GET)->handler($request)
        );
        $this->assertEquals(
            $controller1->handler($request),
            $this->multiTreeNode->findController('/dir/something/option2', RequestMethod::GET)->handler($request)
        );
    }

    // dynamic routes add a middleware to enter the dynamic parameter of the url in the request object

    function testGetNode()
    {
        $request = $this->createMock(Request::class);

        $controller = $this->createController(1);
        $controller2 = $this->createController(2, RequestMethod::POST);
        $group2 = $this->createGroup([
            'action1' => $controller
        ]);

        $group3 = $this->createGroup([
            'action5' => $controller2,
            'sub' => $group2
        ]);
        $group = $this->createGroup([
            'action1' => $controller,
            'group2' => $group2,
            'so' => $group3
        ]);

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
                $controllerItem->handler($request),
                $this->multiTreeNode->findControllerNode(
                    $path,
                    RequestMethod::GET
                )->getController(RequestMethod::GET)->handler($request)
            );
        }
        $this->assertEquals(
            $controller2->handler($request),
            $this->multiTreeNode->findControllerNode(
                'so/action5',
                RequestMethod::POST
            )->getController(RequestMethod::POST)->handler($request)
        );
    }

    function testGetNode_middlewarePassing()
    {
        $request = $this->createMock(Request::class);
        $next = fn () => null;

        $controller = $this->createController(1);

        $mw = $this->createMiddleware('hello');

        $mw2 = $this->createMiddleware('bye');

        $group2 = $this->createGroupWithMiddleware([
            'action1' => $controller
        ], [
            $mw
        ]);

        $group3 = $this->createGroupWithMiddleware([
            'action5' => $controller,
            'sub' => $group2
        ], [
            $mw
        ]);

        $group = $this->createGroupWithMiddleware([
            'action1' => $controller,
            'group2' => $group2,
            'so' => $group3
        ], [
            $mw2
        ]);

        $this->multiTreeNode->bindGroup($group);
        $nodeMiddleware = $this->multiTreeNode->findControllerNode(
            '/group2/action1',
            RequestMethod::GET
        )->getMiddlewareList();

        $this->assertEquals(2, count($nodeMiddleware));
        $this->assertEquals(
            [
                $mw2->handler($request, $next),
                $mw->handler($request, $next)
            ],
            [
                $nodeMiddleware[0]->handler($request, $next),
                $nodeMiddleware[1]->handler($request, $next),
            ]
        );

        $nodeMiddleware = $this->multiTreeNode->findControllerNode(
            '/action1',
            RequestMethod::GET
        )->getMiddlewareList();

        $this->assertEquals(1, count($nodeMiddleware));
        $this->assertEquals(
            $mw2->handler($request, $next),
            $nodeMiddleware[0]->handler($request, $next),
        );

        $nodeMiddleware = $this->multiTreeNode->findControllerNode(
            'so/sub/action1',
            RequestMethod::GET
        )->getMiddlewareList();

        $this->assertEquals(3, count($nodeMiddleware));
        $this->assertEquals(
            [
                $mw2->handler($request, $next),
                $mw->handler($request, $next),
                $mw->handler($request, $next),
            ],
            [
                $nodeMiddleware[0]->handler($request, $next),
                $nodeMiddleware[1]->handler($request, $next),
                $nodeMiddleware[2]->handler($request, $next),
            ]
        );

        $nodeMiddleware = $this->multiTreeNode->findControllerNode(
            'so/action5',
            RequestMethod::GET
        )->getMiddlewareList();

        $this->assertEquals(2, count($nodeMiddleware));
        $this->assertEquals(
            [
                $mw2->handler($request, $next),
                $mw->handler($request, $next),
            ],
            [
                $nodeMiddleware[0]->handler($request, $next),
                $nodeMiddleware[1]->handler($request, $next),
            ]
        );
    }

    function testGetNode_middlewarePassing_multipleBindingForSinglePath()
    {
        $request = $this->createMock(Request::class);
        $next = fn () => null;

        $controller = $this->createController(1);
        $mw = $this->createMiddleware('hello');

        $mw2 = $this->createMiddleware('bye');

        $group = $this->createGroupWithMiddleware([
            'action1' => $controller,
        ], [
            $mw
        ]);

        $group2 = $this->createGroupWithMiddleware([
            'action1' => $controller
        ], [
            $mw2
        ]);

        $this->multiTreeNode->bindGroup($group);
        $this->multiTreeNode->bindGroup($group2);

        $nodeMiddleware = $this->multiTreeNode->findControllerNode(
            '/action1',
            RequestMethod::GET
        )->getMiddlewareList();

        $this->assertEquals(2, count($nodeMiddleware));
        $this->assertEquals(
            [
                $mw->handler($request, $next),
                $mw2->handler($request, $next),
            ],
            [
                $nodeMiddleware[0]->handler($request, $next),
                $nodeMiddleware[1]->handler($request, $next),
            ]
        );
    }

    function testGetNode_middlewarePassing_dynamicPath()
    {
        $request = $this->createMock(Request::class);
        $next = fn () => null;

        $controller = $this->createController(1);
        $controller1 = $this->createController(2);

        $mw = $this->createMiddleware('hello');

        $mw2 = $this->createMiddleware('bye');

        $group = $this->createGroupWithMiddleware([
            ':id' => $controller1,
        ], [
            $mw
        ]);

        $group2 = $this->createGroupWithMiddleware([
            'action1' => $controller
        ], [
            $mw2
        ]);

        $this->multiTreeNode->bindGroup($group);
        $this->multiTreeNode->bindGroup($group2);

        $node = $this->multiTreeNode->findControllerNode(
            '/action1',
            RequestMethod::GET
        );
        $nodeMiddleware = $node->getMiddlewareList();
        $this->assertEquals(
            $controller->handler($request),
            $node->getController(RequestMethod::GET)->handler($request)
        );
        $this->assertEquals(2, count($nodeMiddleware));
        $this->assertEquals(
            [
                $mw->handler($request, $next),
                $mw2->handler($request, $next),
            ],
            [
                $nodeMiddleware[0]->handler($request, $next),
                $nodeMiddleware[1]->handler($request, $next),
            ]
        );

        $node = $this->multiTreeNode->findControllerNode(
            '/somethingDynamic',
            RequestMethod::GET
        );
        $nodeMiddleware = $node->getMiddlewareList();
        $this->assertEquals(
            $controller1->handler($request),
            $node->getController(RequestMethod::GET)->handler($request)
        );
        // <<<<<<<<<<<<<<<<<<<<<<<<< here we should expect another middleware added to queue
        // <<<<<<<<<<<<<<<<<<<<<<<< maybe passing the param.
        // <<<<<<<<<<<<<<<<<<<<<<<<<< or we should ignore this and just edit the request params :thinking
        $this->assertEquals(2, count($nodeMiddleware));
        $this->assertEquals(
            [
                $mw->handler($request, $next),
                $mw2->handler($request, $next),
            ],
            [
                $nodeMiddleware[0]->handler($request, $next),
                $nodeMiddleware[1]->handler($request, $next),
            ]
        );
    }

    function testGetNode_middlewarePassing_notEffectingMainNode()
    {
        $request = $this->createMock(Request::class);
        $next = fn () => null;

        $controller = $this->createController(1);
        $mw = $this->createMiddleware('hello');

        $mw2 = $this->createMiddleware('bye');

        $group = $this->createGroupWithMiddleware([
            'something' => $controller,
        ], [
            $mw
        ]);

        $group2 = $this->createGroupWithMiddleware([
            'gg' => $group
        ], [
            $mw2
        ]);

        $this->multiTreeNode->bindGroup($group2);

        $tree = $this->multiTreeNode->dump();
        $this->assertEquals(1, count($tree['_middlewareList']));
        $this->assertEquals(1, count($tree['gg']['_middlewareList']));
        $this->assertEquals(0, count($tree['gg']['something']['_middlewareList']));
        $this->assertEquals(
            $mw->handler($request, $next),
            $tree['gg']['_middlewareList'][0]->handler($request, $next)
        );
        $this->assertEquals(
            $mw2->handler($request, $next),
            $tree['_middlewareList'][0]->handler($request, $next)
        );
    }

    function testDynamicPathParams()
    {
        $request = $this->createMock(Request::class);

        $controller = $this->createController(1);
        $controller1 = $this->createController(2);

        $this->multiTreeNode->registerController('/dir/:id/option1', $controller);
        $this->multiTreeNode->registerController('/dir/:other/option2', $controller1);

        $this->assertEquals(
            "something",
            $this->multiTreeNode->findControllerNode('/dir/something/option1', RequestMethod::GET)->getRequest()->params->id
        );
        $this->assertEquals(
            "something",
            $this->multiTreeNode->findControllerNode('/dir/something/option2', RequestMethod::GET)->getRequest()->params->other
        );
    }
}
