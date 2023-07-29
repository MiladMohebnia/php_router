<?php

namespace miladm\oldRouter\router;

class RegexName
{
    // define list */
    public static $regex_shorthandName = [
        "number" => '[0-9]+',
        "numberstring" => '[0-9a-zA-Z]+',
        "string" => '[a-zA-Z]+',
        "url" => '[0-9a-zA-Z\_\-\%]+'
    ];

    public static function replace($string)
    {

        // replace all reserved regex like /:name(string)/
        foreach (self::$regex_shorthandName as $shorthand => $regex) {
            $string = preg_replace(
                "/\/:(" . self::$regex_shorthandName["url"] . ")\((" . $shorthand . ")\)\//",
                "/(?<$1>" . $regex . ")/",
                $string
            );
        }
        // $string = preg_replace('/\/:(?<name>[^(]*)\((?<pattern>[^)]*)\)/', "/(?<$1>$2)", $string);

        // replace all unknown regex parameters like /:name/
        $string = preg_replace(
            "/:(" . self::$regex_shorthandName["url"] . ")\//",
            "(?<$1>" . self::$regex_shorthandName["url"] . ")/",
            $string
        );
        return $string;
    }
}
