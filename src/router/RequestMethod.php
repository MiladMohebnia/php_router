<?php

namespace miladm\router;

enum RequestMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case DELETE = 'DELETE';
    case PATCH = 'PATCH';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';
    case CONNECT = 'CONNECT';
    case TRACE = 'TRACE';

    public static function getRequestMethodFrom(string $method): self
    {
        $method = strtoupper($method);
        return match ($method) {
            'GET' => self::GET,
            'POST' => self::POST,
            'PUT' => self::PUT,
            'DELETE' => self::DELETE,
            'PATCH' => self::PATCH,
            'HEAD' => self::HEAD,
            'OPTIONS' => self::OPTIONS,
            'CONNECT' => self::CONNECT,
            'TRACE' => self::TRACE,
            default => throw new \InvalidArgumentException("Invalid request method: $method"),
        };
    }
}
