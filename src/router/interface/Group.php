<?php

namespace miladm\router\interface;

interface Group
{
    /**
     * @return array<string, Group|Controller>
     */
    static function controllerList(): array;
}
