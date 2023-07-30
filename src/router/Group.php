<?php

namespace miladm\router;

abstract class Group
{
    /**
     * @return array<Middleware|empty>
     */
    static function middlewareList(): array
    {
        return [];
    }

    /**
     * @return array<string, Group|Controller>
     */
    static abstract function controllerList(): array;
}
