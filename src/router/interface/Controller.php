<?php

namespace miladm\router\interface;

use miladm\router\Request;

interface Controller
{
    /**
     * @return array<Middleware>
     */
    public function middlewareList(): array;

    public function handler(Request $request): string | int | array;
}
