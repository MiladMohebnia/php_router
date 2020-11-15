<?php

use miladm\Fake;
use miladm\RRoute;

class UserController
{
    public static function _GET($request)
    {
        return 'this is user public get';
    }

    public static function _POST($request)
    {
        return 'user posttttttt!';
    }
}


class SampleController
{
    public static function hi_GET($request)
    {
        return 'HIIII world!';
    }

    public static function _GET($request)
    {
        return 'hello world!';
    }
}

class IndexController
{
    public static function hi_GET($request)
    {
        return 'Index HIIII world!';
    }

    public static function _GET($request)
    {
        return 'Index hello world!';
    }


    public static function _POST($request)
    {
        return 'index posttttttt!';
    }
}


Fake::get('index', ''); // 2Index hello world!
Fake::post('index2', 'sample/hi'); // 2168711111
Fake::post('index3', 'sample/hi2'); // 21hi2
Fake::post('index4', 'ssss'); // 2755555ssss

####
Fake::post('index5', 'sample'); // 271 + return 
#### bug if interceptor match once it will be registered! :| there must be a rollback for interceptors and middle wares

Fake::get('sample', 'sample'); // 21hello world!
Fake::get('sampleHi', 'sample/hi'); // 21HIIII world!
Fake::get('user', 'user'); // 2this is user public get
Fake::post('user2', 'user'); // 27user posttttttt!
Fake::get('userId', 'user/15'); // 215
Fake::run('index5');

$trace = false;
function traceDump(...$data)
{
    global $trace;
    if ($trace) {
        die(var_dump(
            ...$data
        ));
    }
}


RRoute::bind('')
    ->controller(IndexController::class)
    ->interceptor(function ($request, $next) {
        echo 2;
        return $next($request);
    })
    //hashmatch order
    // -> middleware | use
    // -> expect
    ->post(':id', function ($request) {
        return 55555 . $request->param->id;
    })->interceptor(function ($request, $next) {
        echo 7;
        return $next($request);
    });

$trace = true;

RRoute::bind('sample')
    ->controller(SampleController::class)
    ->interceptor(function ($request, $next) {
        echo 1;
        return $next($request);
    })
    // -> middleware | use
    // -> expect
    ->post('hi', function ($request) {
        return 11111;
    })->interceptor(function ($request, $next) {
        echo 687;
        return $next($request);
    })
    ->post(':id', function ($request) {
        return $request->param->id;
    })
    ->post('', function ($request) {
        return json_encode($request);
    });

RRoute::bind('user')
    ->controller(UserController::class)
    ->interceptor(function ($request, $next) {
        return $next($request);
    })
    ->get(':id', function () {
    })->interceptor(function ($request, $next) {
        return $request->param->id;
    });

RRoute::run();
die;
