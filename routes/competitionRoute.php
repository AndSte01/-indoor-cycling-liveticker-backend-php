<?php

/** @var \Laravel\Lumen\Routing\Router $router */

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

$router->group(['prefix' => 'competition'], function () use ($router) {
    /**
     * @var string the controller to use
     */
    $controller = 'competitionController';

    $router->get('', $controller . '@show');

    $router->post('', ['middleware' => 'auth:token', 'uses' => $controller . '@upsert']);

    $router->delete('', ['middleware' => 'auth:token', 'uses' => $controller . '@destroy']);
});
