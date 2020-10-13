<?php

use miladm\Route;
use miladm\router\Request;



/**
 * bug on middleware next() and return
 * check interceptor
 * assign a single route to a class method
 * alias a route to a class
 * name controller for route!
 */

class MasterRouter
{
    public static function _get(Request $request)
    {
        return 'you are in master route';
    }

    public static function group_asdf($request)
    {
        return 'this is main';
    }

    public static function group_subscribe_add_GET($request)
    {
        return 'level 1';
    }
}

class GroupRoute
{
    public static function _get(Request $request)
    {
        return 'you are in group Route';
    }

    public static function asdf(Request $request)
    {
        return 'this is sparta';
    }

    public static function subscribe($request)
    {
        return 'I will come first :)';
    }

    public static function group_subscribe_add_GET($request)
    {
        return 'level 2';
    }
}

class SubscribeRoute
{
    public static function _get(Request $request)
    {
        return 'you are in subscribe Route';
    }

    public static function asdf(Request $request)
    {
        return 'this is sparta';
    }

    public static function adda($request)
    {
        return 'level 3';
    }
}

// Route::get('', MasterRouter::class);
Route::register([
    '/' => MasterRouter::class,
    'group' => GroupRoute::class,
    '/group/subscribe' => SubscribeRoute::class
]);
Route::run();
