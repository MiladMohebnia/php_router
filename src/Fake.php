<?php

namespace miladm;

use miladm\faker\Faker;

class Fake
{

    static $faker = null;

    static function get($path, $data = [])
    {
        $faker = self::faker($path);
        return $faker->get($data);
    }

    static function post($path, $data = [])
    {
        $faker = self::faker($path);
        return $faker->post($data);
    }

    private static function faker($path): Faker
    {
        if (self::$faker === null) {
            self::$faker = new Faker($path);
        }
        return self::$faker;
    }
}
