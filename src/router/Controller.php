<?php

namespace miladm\router;

use miladm\router\RequestMethod;
use miladm\router\Request;

abstract class Controller
{
    /**
     * @return array<Middleware>
     */
    static function middlewareList(): array
    {
        return [];
    }

    static abstract function handler(Request $request): string | int | array;

    static function requestMethod(): RequestMethod
    {
        return RequestMethod::GET;
    }
}
