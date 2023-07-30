<?php

namespace miladm\router;

use miladm\router\RequestMethod;
use miladm\router\Request;

abstract class Controller
{
    static abstract function handler(Request $request): string | int | array;

    static function requestMethod(): RequestMethod
    {
        return RequestMethod::GET;
    }
}
