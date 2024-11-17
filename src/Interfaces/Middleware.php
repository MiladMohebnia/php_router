<?php

declare(strict_types=1);

namespace Router\Interfaces;

use Router\Request;

interface Middleware
{
    /**
     * @param \Router\Request $request
     * @param callable $next
     * @return string|int|array<string, mixed>
     */
    public function handler(Request $request, callable $next): string | int | array;
}
