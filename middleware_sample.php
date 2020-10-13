<?php

use miladm\Route;
use miladm\router\Request;

Route::use(function (Request $request) {
    $a = 1;
    return ["status" => 'fail'];
});


Route::alias('/', MainRoute::class);

class MainRoute
{
    public static function _GET(Request $request)
    {
        return 'here we go';
    }

    public static function index(Request $request)
    {
        return ['status' => 'ok'];
    }
}

Route::run();
