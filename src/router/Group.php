<?php

namespace miladm\router;

abstract class Group
{
    /**
     * @return array<string, Group|Controller|array<Controller>>
     */
    abstract function controllerList(): array;
}
