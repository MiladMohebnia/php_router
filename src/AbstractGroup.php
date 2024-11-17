<?php

declare(strict_types=1);

namespace Router;

abstract class AbstractGroup
{
    /**
     * @return array<string, AbstractGroup|AbstractController|array<AbstractController>>
     */
    abstract function controllerList(): array;
}
