<?php

declare(strict_types=1);

namespace Router\Interfaces;

interface UseMiddleware
{
    /**
     * @return array<Middleware>
     */
    public function middlewareList(): array;
}
