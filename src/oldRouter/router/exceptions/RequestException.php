<?php

namespace miladm\oldRouter\router\exceptions;

class RequestException extends \Exception
{

    public function __construct($message, $requestData, $code = 0, \Exception $previous = NULL)
    {
        $message = json_encode($requestData, JSON_PRETTY_PRINT) . PHP_EOL . $message;
        parent::__construct($message, $code, $previous);
    }
}
