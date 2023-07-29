<?php

namespace miladm\oldRouter\router;


class Request extends DefaultRequestDataObject
{

    // for request attributes read DefaultRequestDataObject

    function __construct()
    {
        $this->getMethod();
        $this->getUri();
        $this->getPathAndQuery();
        $this->getRequestAndRequestHash();
        $this->parseQuery();
        $this->parseBody();
        $this->getSessionAndCookie();
    }

    public function checkIfMatch($route)
    {
        if ($this->hashMatch) {
            return false;
        }
        $route = $this->routeToRegex($route);
        if ($this->checkIfHashMatch($route)) {
            $this->hashMatch = true;
            return true;
        }
        if ($this->checkIfRegexMatchAndParseInputParams($route)) {
            return true;
        }
        return false;
    }

    public function checkIfMatch_alias($route)
    {
        if ($this->checkIfMatch($route)) {
            return true;
        }
        if ($route[strlen($route) - 1] != '/') {
            $route .= '/';
        }
        $route .= '.*';
        return $this->checkIfMatch($route);
    }

    public function checkIfHashMatch($route)
    {
        $route = str_replace('(\/|)', '\/', $route);
        return $this->requestHash == md5($route);
    }

    public function attach(array $data): Request
    {
        $attachment = (array) $this->attachment;
        $attachment += $data;
        $this->attachment = (object) $attachment;
        return $this;
    }

    private function getMethod()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? "GET";
    }

    private function getUri()
    {
        $this->requestUri = \urldecode($_SERVER["REQUEST_URI"] ?? '');
    }

    private function getPathAndQuery()
    {
        $this->path = parse_url($this->requestUri)["path"];
        $this->query = parse_url($this->requestUri)["query"] ?? '';
    }

    private function getRequestAndRequestHash()
    {
        $url_path = $this->path;
        if (strlen($url_path) == 0 || $url_path[\strlen($url_path) - 1] != "/") {
            $url_path .= "/";
        }
        $this->request = $url_path;
        $url_path = str_replace("/", "\/", $url_path);
        $this->requestHash = md5($url_path);
    }

    private function parseQuery()
    {
        $this->get  = count($_GET) ? (object)$_GET : null;
        if ($this->get === null) {
            if ($this->query != '') {
                //parseQuery here
            }
        }
    }

    private function parseBody()
    {
        $this->post = $this->method != "GET" ? $this->read_post() : false;
        $this->file = count($_FILES) ? (object)$_FILES : null;
    }

    private function getSessionAndCookie()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION)) {
            $this->session = count($_SESSION) ? (object)$_SESSION : null;
        }
        if (isset($_COOKIE)) {
            $this->cookie = count($_COOKIE) ? (object)$_COOKIE : null;
        }
    }

    private function read_post()
    {
        if (count($_POST))
            return (object)$_POST;
        $input = file_get_contents("php://input");
        if ($input != null) {
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
            } else {
                $headers = $_SERVER;
            }
            $contentType = $headers['CONTENT_TYPE'] ?? $headers["Content-Type"] ?? false;
            if (strpos(strtolower($contentType), 'application/json') !== false)
                return json_decode($input);
            else
                return  $input;
        }
        return false;
    }

    private function routeToRegex($route)
    {

        // make sure the path starts and stop with slash "/"
        if ($route == "" || $route[0] != "/") {
            $route = "/" . $route;
        }
        if ($route[strlen($route) - 1] != "/") {
            $route .= "/";
        }
        $route = RegexName::replace($route);

        // handling last slash situations with optional slash at the end of route (path)
        $route = substr($route, 0, strlen($route) - 1) . '(/|)';

        // replace all slashes
        $route =  str_replace("/", "\/", $route);
        return $route;
    }

    private function checkIfRegexMatchAndParseInputParams($route)
    {
        if (preg_match("/^" . $route . "$/", $this->request, $matchList)) {
            $this->param = (object) $matchList;
            return true;
        }
        return false;
    }
}
