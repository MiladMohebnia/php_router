<?php

namespace miladm\router\exceptions;

use Exception;

class ControllerNotFound extends Exception
{
    public function __construct($path)
    {
        $message = "no controller found for path: $path";
        parent::__construct($message, 404);
    }
}
