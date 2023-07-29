<?php

namespace miladm\router\interface;

interface Group
{
    /**
     * @return array<Middleware>
     */
    public function middlewareList(): array;

    /**
     * @return array<string, Group|Controller>
     */
    public function controllerList(): array;
}
