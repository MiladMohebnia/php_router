<?php

namespace miladm\oldRouter\router;

defined('DEVMODE') ?: define('DEVMODE', false);

class Response
{
    public static function send($data)
    {
        if (self::isDataType($data)) {
            self::responseJson($data);
        } else {
            self::responseString($data);
        }
    }

    public static function isDataType($data)
    {
        return in_array(gettype($data), ['array', 'object']);
    }

    public static function responseJson($data)
    {
        if (ob_get_length() > 0) {
            ob_clean();
        }
        if (headers_sent($file, $line)) {
            trigger_error("remove the output data from file '$file:$line' line '$line'");
            die;
        }
        header('Content-Type: application/json');
        echo json_encode($data, DEVMODE ? JSON_PRETTY_PRINT : 0);
        die();
    }

    public static function responseString($data)
    {
        if (ob_get_length() > 0) {
            ob_clean();
        }
        echo $data;
        die();
    }
}
