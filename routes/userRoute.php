<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Http\Controllers\competitionController;

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

$router->group(['prefix' => 'user', 'middleware' => 'auth:password'], function () use ($router) {
    /**
     * @var string the controller to use
     */
    $controller = 'userController';

    $router->get('', $controller . '@show');

    $router->post('', $controller . '@upsert');

    $router->delete('', $controller . '@destroy');
});

$router->get('user/validate_token', ['middleware' => 'auth:token', function () {
    return "SUCCESS";
}]);
