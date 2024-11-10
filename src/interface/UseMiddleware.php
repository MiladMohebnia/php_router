<?php

declare(strict_types=1);

namespace miladm\interface;

interface UseMiddleware
{
    /**
     * @return array<Middleware>
     */
    public function middlewareList(): array;
}
