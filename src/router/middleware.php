<?php

namespace miladm\router;

abstract class Middleware
{
    abstract function handler(Request $request, callable $next);
}
