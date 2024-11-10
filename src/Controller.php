<?php

declare(strict_types=1);

namespace Router;

use Router\RequestMethod;
use Router\Request;

abstract class Controller
{
    abstract function handler(Request $request): string | int | array;

    function requestMethod(): RequestMethod
    {
        return RequestMethod::GET;
    }
}
