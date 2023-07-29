<?php

namespace miladm\oldRouter\faker;

defined('DEVMODE') ?: define('DEVMODE', false);

class Faker
{

    function __construct($URI)
    {
        if ($this->checkIfCLI()) {
            if (($URI[0] ?? "") != "/") {
                $URI = '/' . $URI;
            }
            $_SERVER['REQUEST_URI'] = $URI;
            $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
            $_SERVER['HTTP_USER_AGENT'] = 'CLI';
            if (isset($_SESSION['__COOKIE'])) {
                $_COOKIE = $_SESSION['__COOKIE'];
            }
        }
    }

    public function post($data = [])
    {
        if (!$this->checkIfCLI()) {
            return $this;
        }
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $data;
        return $this;
    }

    public function get($data = [])
    {
        if (!$this->checkIfCLI()) {
            return $this;
        }
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = $data;
        return $this;
    }

    public function add_cookie($data = [])
    {
        if (!$this->checkIfCLI()) {
            return $this;
        }
        $_COOKIE += $data;
        return $this;
    }

    private function checkIfCLI()
    {
        return (DEVMODE && php_sapi_name() === 'cli');
    }
}
