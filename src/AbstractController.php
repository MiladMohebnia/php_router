<?php

declare(strict_types=1);

namespace Router;

use Router\RequestMethod;
use Router\Request;

abstract class AbstractController
{
    /**
     * @param \Router\Request $request
     * @return string|int|array<string, mixed>
     */
    abstract function handler(Request $request): string | int | array;

    function requestMethod(): RequestMethod
    {
        return RequestMethod::GET;
    }
}
