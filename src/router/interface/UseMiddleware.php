<?php

namespace miladm\router\interface;

interface UseMiddleware
{
    /**
     * @return array<Middleware|empty>
     */
    static function middlewareList(): array;
}
