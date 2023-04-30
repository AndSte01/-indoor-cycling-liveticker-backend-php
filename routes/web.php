<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use Illuminate\Http\JsonResponse;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return new JsonResponse([
        "url" => env('APP_URL'),
        "api" => env('API_VERSION'),
        "backend" => env('APP_NAME'),
        "backend_version" => env('APP_VERSION')
    ]);
});
