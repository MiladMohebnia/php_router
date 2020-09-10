<?php

use miladm\router\exceptions\RequestException;
use miladm\router\Request;

define('DEVMODE', true);

include "vendor/autoload.php";


$faker = new \miladm\faker\Faker("/home/123/home1111");
$faker->post([
    "auth" => 'admin',
]);
$r = new \miladm\router\Router();

$r->globalMiddleware_add(function ($request) {
    $request->method .= "a";
    return $request;
});
$r->globalMiddleware_add(function ($request) {
    $request->method .= "b";
    return $request;
});

$r->any('home/:id(number)/:home', function ($request) {
    return (object)['a', 'b', 'c'];
    return 'hello world!';
})->use(function (Request $request) {
    $request->method .= "x";
    return $request;
});

$r->post('home/123/home1111', function ($request) {
    return (object)['a'];
    return 'hello world!';
})->use(function (Request $request) {
    $request->method .= "x";
    return $request;
});



// $r->globalInterceptor_add(function ($request, $next) {
//     var_dump($request->method);
//     $request->method .= '1';
//     $result = $next($request);
//     (var_dump(
//         "from first interceptor",
//         $result
//     ));
//     return $result . '1';
// });
// $r->get('home/:id(number)/:home', function ($request) {
//     var_dump($request->method);
//     return 'hello world!';
// })->interceptor_add(function ($request, $next) {
//     var_dump($request->method);
//     $request->method .= '4';
//     $result = $next($request);
//     (var_dump(
//         "from first interceptor",
//         $result
//     ));
//     return $result . '4';
// })->interceptor_add(function ($request, $next) {
//     var_dump($request->method);
//     $request->method .= '3';
//     $result = $next($request);
//     (var_dump(
//         "from first interceptor",
//         $result
//     ));
//     return $result . '3';
// });
// $r->globalInterceptor_add(function ($request, $next) {
//     var_dump($request->method);
//     $request->method .= '2';
//     $result = $next($request);
//     (var_dump(
//         "from second interceptor",
//         $result
//     ));
//     return $result . '2';
// });


$r->run();
