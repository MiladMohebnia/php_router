<?php

namespace miladm\router\interface;

interface UseMiddleware
{
    /**
     * @return array<Middleware>
     */
    public function middlewareList(): array;
}
