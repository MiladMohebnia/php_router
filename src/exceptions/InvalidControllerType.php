<?php

declare(strict_types=1);

namespace miladm\exceptions;

use Exception;

class InvalidControllerType extends Exception
{
    public function __construct(
        $controllerOrGroup,
        $path,
    ) {
        $message = 'unsupported call back object ['
            . gettype($controllerOrGroup) . "] for path [$path]."
            . ' it must be instance of group or controller';
        parent::__construct($message);
    }
}
