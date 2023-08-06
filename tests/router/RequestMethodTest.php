<?php

namespace miladmTest\router;

use miladm\router\RequestMethod;
use PHPUnit\Framework\TestCase;

class RequestMethodTest extends TestCase
{
    function testGetRequestMethodFromString()
    {
        $target = RequestMethod::getRequestMethodFrom("GET");
        $this->assertEquals(RequestMethod::GET, $target);
    }

    function testGetRequestMethodFromString_post()
    {
        $target = RequestMethod::getRequestMethodFrom("POST");
        $this->assertEquals(RequestMethod::POST, $target);
    }

    function testGetRequestMethodFromString_fromSmallCharacters()
    {
        $target = RequestMethod::getRequestMethodFrom("post");
        $this->assertEquals(RequestMethod::POST, $target);
    }
}
