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
$routes->group('', ['namespace' => 'App\Controllers\Api\Protected'], function($routes) {
    $routes->get('getSumPemberat', 'CalculateIndexController::sumPemberat');
    $routes->get('getPeratusKomponen', 'CalculateIndexController::peratusKomponen');
    $routes->post('getCalculatePengiraanIndeks', 'CalculateIndexController::calculatePengiraanIndeks');
});