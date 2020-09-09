<?php

namespace miladm\faker;

class Faker
{

    function __construct($URI)
    {
        $_SERVER['REQUEST_URI'] = $URI;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'CLI';
        if (isset($_SESSION['__COOKIE'])) {
            $_COOKIE = $_SESSION['__COOKIE'];
        }
    }

    public function post($data = [])
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $data;
        return $this;
    }

    public function get($data = [])
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = $data;
        return $this;
    }

    public function add_cookie($data = [])
    {
        $_COOKIE += $data;
        return $this;
    }
}
