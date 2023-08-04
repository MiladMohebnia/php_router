<?php

namespace miladmTest\router;

use miladm\router\Controller;
use miladm\router\MultiTreeNode;
use miladm\router\RequestMethod;
use PHPUnit\Framework\TestCase;

class MultiTreeNodeTest extends TestCase
{

    private $multiTreeNode;

    protected function setUp(): void
    {
        $this->multiTreeNode = new MultiTreeNode;
    }

    function testPathNames()
    {
        $pathList = [
            "/" => "",
            "/index" => "",
            "index" => "",
            "" => "",
            "/index-other" => "index-other",
            "index-other" => "index-other",
            "index/" => "",
            "/index/" => "",
            "index-other/" => "index-other",
            "/index-other/" => "index-other",
        ];
        /** @var MockObject $controller */
        $controller = $this->createMock(Controller::class);
        $controller->method('requestMethod')->willReturn(RequestMethod::GET);
        foreach ($pathList as $path => $expectation) {
            $this->multiTreeNode = new MultiTreeNode;
            /** @var Controller $controller */
            $this->multiTreeNode->registerController($path, $controller);
            $this->assertArrayHasKey($expectation, $this->multiTreeNode->dump(), json_encode($this->multiTreeNode->dump()));
        }
    }
}
