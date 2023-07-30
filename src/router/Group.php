<?php

namespace miladm\router;

abstract class Group
{
    /**
     * @return array<string, Group|Controller>
     */
    abstract function controllerList(): array;
}
