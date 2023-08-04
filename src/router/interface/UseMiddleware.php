<?php

namespace miladm\router\interface;

interface UseMiddleware
{
    /**
     * @return array<Middleware|empty>
     */
    public function middlewareList(): array;
}
