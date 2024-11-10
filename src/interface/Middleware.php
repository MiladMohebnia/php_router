<?php

declare(strict_types=1);

namespace Router\interface;

use Router\Request;

interface Middleware
{
    public function handler(Request $request, callable $next);
}
