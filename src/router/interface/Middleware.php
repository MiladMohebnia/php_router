<?php

namespace miladm\router\interface;

use miladm\oldRouter\router\Request;

interface Middleware
{
    public static function handler(Request $request, callable $next);
}
