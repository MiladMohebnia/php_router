<?php

declare(strict_types=1);

namespace Router\interface;

interface UseMiddleware
{
    /**
     * @return array<Middleware>
     */
    public function middlewareList(): array;
}
