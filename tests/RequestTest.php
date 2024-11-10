<?php

declare(strict_types=1);

namespace miladmTest;

use InvalidArgumentException;
use miladm\Request;
use miladm\RequestMethod;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{

    function setUp(): void
    {
        $_POST = [];
        $_SERVER = [];
        $_FILES = [];
    }

    function testMethods()
    {
        $inputList = [
            'post' => RequestMethod::POST,
            'POST' => RequestMethod::POST,
            'get' => RequestMethod::GET,
            'gEt' => RequestMethod::GET,
            'delete' => RequestMethod::DELETE,
        ];
        foreach ($inputList as $string => $enum) {
            $_SERVER['REQUEST_METHOD'] = $string;
            $request = new Request;
            $this->assertEquals(
                $enum,
                $request->getRequestMethod()
            );
        }
    }

    function testSetRequestMethod()
    {
        $request = new Request;
        $request->setRequestMethod(RequestMethod::POST);
        $this->assertEquals(
            RequestMethod::POST,
            $request->getRequestMethod()
        );
    }

    function testAddParam()
    {
        $request = new Request;
        $key = "testKey";
        $value = "testValue";
        $request->addParam($key, $value);
        // $this->assertObjectHasAttribute($key, $request->params);
        $this->assertEquals($value, $request->params->$key);
    }

    function testRequestUriProperty()
    {
        $_SERVER['REQUEST_URI'] = "/sample/path?param=value";
        $request = new Request;
        $this->assertEquals("/sample/path?param=value", $request->requestUri);
    }

    function testPathAndQueryProperties()
    {
        $_SERVER['REQUEST_URI'] = "/sample/path?param=value";
        $request = new Request;
        $this->assertEquals("/sample/path", $request->path);
        $this->assertEquals("param=value", $request->query);
    }

    function testSessionProperty()
    {
        $_SESSION = [
            "username" => "testUser",
            "role" => "admin"
        ];
        $request = new Request;
        $this->assertEquals((object)$_SESSION, $request->session);
    }

    function testCookieProperty()
    {
        $_COOKIE = [
            "sessionId" => "123456",
            "preferences" => "darkMode"
        ];
        $request = new Request;
        $this->assertEquals((object)$_COOKIE, $request->cookie);
    }

    function testPostProperty()
    {
        $_SERVER['REQUEST_METHOD'] = 'post';
        $_POST = [
            "username" => "testUser",
            "password" => "testPass"
        ];
        $request = new Request;
        $this->assertEquals((object)$_POST, $request->post);
    }

    function testUriParsingWithPathOnly()
    {
        $_SERVER['REQUEST_URI'] = "/sample/path";
        $request = new Request;
        $this->assertEquals("/sample/path", $request->requestUri);
        $this->assertEquals("/sample/path", $request->path);
        $this->assertEquals("", $request->query);
    }

    function testUriParsingWithQueryOnly()
    {
        $_SERVER['REQUEST_URI'] = "?param=value";
        $request = new Request;
        $this->assertEquals("?param=value", $request->requestUri);
        $this->assertEquals("", $request->path);
        $this->assertEquals("param=value", $request->query);
    }

    function testUriParsingWithoutRequestUri()
    {
        unset($_SERVER['REQUEST_URI']);
        $request = new Request;
        $this->assertEquals("", $request->requestUri);
        $this->assertEquals("", $request->path);
        $this->assertEquals("", $request->query);
    }

    function testInvalidRequestMethod()
    {
        $this->expectException('InvalidArgumentException');
        $_SERVER['REQUEST_METHOD'] = 'INVALID_METHOD';
        new Request;
    }
}
