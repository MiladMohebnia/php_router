<?php

declare(strict_types=1);

namespace Router\Interfaces;

use Router\Request;

interface Middleware
{
    public function handler(Request $request, callable $next);
}
