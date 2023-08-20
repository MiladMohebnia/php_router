<?php

namespace miladm\router;

use miladm\router\RequestMethod;

class Request
{
    public RequestMethod $requestMethod;
    public string $requestUri;
    public string $path;
    public string $query = '';
    public ?object $get = null;
    public ?object $post = null;
    public ?object $file = null;
    public ?object $session = null;
    public ?object $cookie = null;
    public ?object $params = null;
    public ?object $attachment = null;

    function __construct()
    {
        $this->registerRequestMethod();
        $this->registerRequestUri();
        $this->registerRequestPathAndQuery();
        // $this->registerRequestRequestAndRequestHash();
        $this->parseQuery();
        $this->parseBody();
        $this->registerRequestSessionAndCookie();
    }

    public function addParam($key, $value): void
    {
        if (!$this->params) {
            $this->params = (object) [$key => $value];
        } else {
            $this->params->{$key} = $value;
        }
    }

    public function getRequestMethod(): RequestMethod
    {
        return $this->requestMethod;
    }

    public function attach(array $data): self
    {
        $attachment = (array) $this->attachment;
        $attachment += $data;
        $this->attachment = (object) $attachment;
        return $this;
    }

    public function setRequestMethod(RequestMethod $requestMethod): void
    {
        $this->requestMethod = $requestMethod;
    }

    private function registerRequestMethod(): void
    {
        $this->setRequestMethod(
            RequestMethod::getRequestMethodFrom($_SERVER['REQUEST_METHOD'] ?? null)
                ?? RequestMethod::GET
        );
    }

    private function registerRequestUri(): void
    {
        $this->requestUri = \urldecode($_SERVER['REQUEST_URI'] ?? '');
    }

    private function registerRequestPathAndQuery(): void
    {
        $this->path = parse_url($this->requestUri)['path'] ?? '';
        $this->query = parse_url($this->requestUri)['query'] ?? '';
    }

    private function parseQuery(): void
    {
        $this->get  = count($_GET) ? (object)$_GET : null;
        if ($this->get === null && $this->query != '') {
            parse_str($this->query, $result);
            $this->get = (object)$result;
        }
    }

    private function parseBody(): void
    {
        $this->post =
            $this->requestMethod != RequestMethod::GET ?
            $this->read_post() : (object) [];
        $this->file = count($_FILES) ? (object)$_FILES : null;
    }

    private function registerRequestSessionAndCookie(): void
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

    private function read_post(): object | null
    {
        if (count($_POST))
            return (object)$_POST;
        $input = file_get_contents('php://input');
        if ($input != null) {
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
            } else {
                $headers = $_SERVER;
            }
            $contentType = $headers['CONTENT_TYPE'] ?? $headers['Content-Type'] ?? false;
            if (strpos(strtolower($contentType), 'application/json') !== false)
                return json_decode($input);
            else
                return $input;
        }
        return null;
    }
}
