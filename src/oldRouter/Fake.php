<?php

namespace miladm;

use miladm\oldRouter\faker\Faker;

class Fake
{

    static $fakeList = [];

    static function get($name, $path, $data = [])
    {
        self::$fakeList[$name] = [
            'method' => 'GET',
            'path' => $path,
            'data' => $data
        ];
    }

    static function post($name, $path, $data = [])
    {
        self::$fakeList[$name] = [
            'method' => 'POST',
            'path' => $path,
            'data' => $data
        ];
    }

    public static function run($name)
    {
        $runnerParams = self::$fakeList[$name];
        $faker = new Faker($runnerParams['path']);
        switch ($runnerParams['method']) {
            case 'GET':
                $faker->get($runnerParams['data']);
                break;
            case 'POST':
                $faker->post($runnerParams['data']);
                break;
        }
    }
}
