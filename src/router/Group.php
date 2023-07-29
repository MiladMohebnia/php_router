<?php

namespace miladm\router;

abstract class Group
{
    /**
     * @return Middleware[]
     */
    protected function middlewareList()
    {
    }
}
