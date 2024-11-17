<?php

declare(strict_types=1);

namespace Router\Exceptions;

use Exception;

class ControllerNotFound extends Exception
{
    public function __construct($path)
    {
        $message = "no controller found for path: $path";
        parent::__construct($message, 404);
    }

    public function showErrorPage()
    {
        http_response_code(404);
        return ['message' => 'page not found', 'error_code' => 404];
    }
}
