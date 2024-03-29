<?php

namespace miladm\router;

use miladm\router\RequestMethod;
use miladm\router\Request;

abstract class Controller
{
    abstract function handler(Request $request): string | int | array;

    function requestMethod(): RequestMethod
    {
        return RequestMethod::GET;
    }
}
