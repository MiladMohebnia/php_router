<?php

namespace miladm\oldRouter\router;

class DefaultRequestDataObject
{
    public $method  = "Get";
    public $requestUri = "";
    public $path    = "";
    public $query   = "";
    public $request = "";
    public $requestHash = "";
    public $get      = null;
    public $post     = null;
    public $file     = null;
    public $param    = null;
    public $session  = null;
    public $cookie   = null;
    public $attachment = null;
    public $hashMatch = false;

    function __construct()
    {
        $this->get = (object) [];
        $this->post = (object) [];
        $this->file = (object) [];
        $this->param = (object) [];
        $this->session = (object) [];
        $this->cookie = (object) [];
        $this->attachment = (object) [];
    }
}
