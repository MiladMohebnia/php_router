<?php

declare(strict_types=1);

namespace miladm;

use miladm\RequestMethod;
use miladm\Request;

abstract class Controller
{
    abstract function handler(Request $request): string | int | array;

    function requestMethod(): RequestMethod
    {
        return RequestMethod::GET;
    }
}
