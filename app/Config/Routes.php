<?php

use CodeIgniter\Router\RouteCollection;

// public endpoint
$routes->group('ikka-be-dashboard', ['namespace' => 'App\Controllers\Api\Public'], function($routes) {
    $routes->post('auth/superAdminLogin', 'AuthController::superAdminLogin');
    $routes->post('auth/userAdminLogin', 'AuthController::userAdminLogin');
    $routes->post('auth/userOpLogin', 'AuthController::userOpLogin');
});

// protected endpoint
$routes->group('ikka-be-dashboard', ['namespace' => 'App\Controllers\Api\Protected'], function($routes) {
    $routes->get('test', 'DashboardController::test');
});

// test db
$routes->get('testDB', 'TestDB::testDB');
$routes->get('listDB', 'TestDB::listDB');

// calculate index
// $routes->group('', ['namespace' => 'App\Controllers\Api\Protected'], function($routes) {
//     $routes->post('postLogin', 'AuthenticationController::userLogin');
// });

// calculate index
$routes->group('', ['namespace' => 'App\Controllers\Api\Protected'], function($routes) {
    $routes->get('getSumPemberat', 'CalculateIndexController::sumPemberat');
    $routes->get('getPeratusKomponen', 'CalculateIndexController::peratusKomponen');
    $routes->post('postCalculatePengiraanIndeks', 'CalculateIndexController::calculatePengiraanIndeks');
});

$routes->group('', ['filter' => 'cors'], static function (RouteCollection $routes) {
    $routes->post('postLogin', 'Api\Protected\AuthenticationController::userLogin');
    $routes->options('postLogin', static function () {
        $response = response();
        $response->setStatusCode(204);
        $response->setHeader('Allow', 'OPTIONS, POST');
        return $response;
    });
});

$routes->group('', ['filter' => 'cors'], static function (RouteCollection $routes) {
    $routes->get('getUserIndikator', 'Api\Protected\AuthenticationController::loadUserIndikator');
    $routes->options('getUserIndikator', static function () {
        $response = response();
        $response->setStatusCode(204);
        $response->setHeader('Allow', 'OPTIONS, GET');
        return $response;
    });
});