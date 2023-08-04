<?php

namespace miladm\router\interface;

use miladm\oldRouter\router\Request;

interface Middleware
{
    public function handler(Request $request, callable $next);
}
