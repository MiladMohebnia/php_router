# Configuration and setup

so easy:

1. install package from composer
1. add `autoloader` to your index file
1. setup routes as instruction (below)
1. at the end of the file write `Route::run()`

```php
<?php
include 'vendor/autoload.php';
use miladm\Route;

Route::.... // config routes here

Route::run();
?> // <-- not necessary I mean it's the end of the file
```

# Simple routing

## available methods

| methods     | parameters                               | description                                  |
| ----------- | ---------------------------------------- | -------------------------------------------- |
| bind        | path: string                             | bind a master route path to controller       |
| use         | callback: callableFunction               | register global middleware                   |
| get         | route:string, callback: callableFunction | handle singe get method route                |
| post        | route:string, callback: callableFunction | handle single post method route              |
| any         | route:string, callback: callableFunction | handle single both get and post method route |
| alias       | route:string, callback: callableFunction |                                              |
| register    | routeList: array                         |                                              |
| interceptor | callback: callableFunction               | register global interceptor                  |
| expect      | class:RequestDataObject                  | change request data in specific dataObject   |
| run         |                                          | runs the router                              |

## request

there's a request variable that will enter the router function

```php
 Route::get('url/goes/here', function(\miladm\oldRouter\router\Request request) {
     return 'hello world~!';
 });
```

type `\miladm\oldRouter\router\Request` is as below :

| name       | type   | description                                           |
| ---------- | ------ | ----------------------------------------------------- |
| method     | string | it's either GET or POST                               |
| get        | object | every values sent in url query                        |
| post       | object | every values sent through request body in post method |
| param      | object | all url parameters from regular expressions           |
| file       | object | equals to $\_FILE                                     |
| session    | object | all data in session                                   |
| cookie     | object | all data in cookie                                    |
| attachment | object | data that passed from interceptors and middle-wares   |
| requestUri | string | request URI                                           |
| path       | string | requested path                                        |
| query      | string | requested query                                       |

## response

anything you return in the router function will be the response;
supported types:

| type      | response type     |
| --------- | ----------------- |
| string    | string            |
| array     | JSON              |
| object    | JSON              |
| exception | Error page <HTML> |

## GET method

```php
 Route::get('url/goes/here', function(request) {
     return 'hello world~!';
 });
```

## POST method

```php
 Route::post('url/goes/here', function(request) {
     return 'hello world~!';
 });
```

## All methods

```php
 Route::any('url/goes/here', function($request) {
     return 'hello world~!';
 });
```

## Interceptor

For all routes

```php
Route::interceptor(function($request, $next){ .... });
```

For specific route

```php
Route::get('/home', function($request){ ... })
    ->interceptor(function($request, $next) { ... });
```

## middleware

**note** if middleware returns anything, the main function won't be called. it's good for handling errors and validating requests.

For all routes

```php
Route::use(function($request, $next){ .... });
```

For specific route

```php
Route::get('/home', function($request){ ... })
    ->use(function($request) { ... });
```

# Bind routes to controller

```php
    Route::bind('/user')
        ->controller(new UserController());
```

here we bind all `/user` and `/user/*` to `UserController`.

## create Controller class

to create method for each route we can add `_GET` and `_POST` ah the end of each method.
however it supports if we don't add this method type it will work as `any` route registration.

> Note: you can use lower-case `_get` and `_post` but we recommend to use uppercase `_GET` and `_POST` as the algorithm is looking for uppercase methods first.

```php

Route::bind('/user')
    ->controller(new UserController());

class UserController
{
    // this goes for http://localhost/user
    public static function _GET(Request $request)
    {
        return 'hello world';
    }


    // this goes for http://localhost/user/login with GET method
    public static function login_GET(Request $request)
    {
        return [
            'status' => 'login'
        ];
    }

    // this goes for http://localhost/user/login with POST method
    public static function login_POST(Request $request)
    {
        $userName = $request->post->userName;
        $password = $request->post->password;
    }
}
```

> you can add middleware and interceptor for the bind class as well

```php
    Route::bind('/user')
        ->controller(new UserController())
        ->use(...)
        ->interceptor(...);
```

> documentation is in progress but code talks itself. checkout the code for more.
