<?php

declare(strict_types=1);

namespace Router;

abstract class Group
{
    /**
     * @return array<string, Group|Controller|array<Controller>>
     */
    abstract function controllerList(): array;
}
