<?php

declare(strict_types=1);

namespace miladm\interface;

use miladm\Request;

interface Middleware
{
    public function handler(Request $request, callable $next);
}
