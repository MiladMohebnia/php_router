<?php

namespace miladm\router;

abstract class Controller
{
    /**
     * @return Middleware[]
     */
    protected function middlewareList(): array
    {
        return [];
    }

    abstract function handler();


    protected function version(): string
    {
        return "v1";
    }

    protected function validatedVersion(): ?string
    {
        return preg_match('/^v\d+$/', $this->version()) !== false ? $this->version() : null;
    }
}
