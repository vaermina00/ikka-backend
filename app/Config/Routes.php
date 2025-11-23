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

$routes->group('', ['filter' => 'cors'], static function (RouteCollection $routes) {
    $routes->get('getSumPemberat', 'Api\Protected\CalculateIndexController::sumPemberat');
    $routes->get('getPeratusKomponen', 'Api\Protected\CalculateIndexController::peratusKomponen');
    $routes->get('getIndikatorCsv', 'Api\Protected\CalculateIndexController::generateIndikatorCsv');
    $routes->post('postCalculateIndikatorIndeks', 'Api\Protected\CalculateIndexController::calculateIndikator');
    $routes->post('postCalculatePengiraanIndeks', 'Api\Protected\CalculateIndexController::calculatePengiraanIndeks');

    $routes->options('(:any)', static function ($any) {
        $response = service('response');
        return $response->setStatusCode(204)
                        ->setHeader('Access-Control-Allow-Origin', 'http://localhost')
                        ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                        ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    });
});

$routes->group('', ['filter' => 'cors'], static function (RouteCollection $routes) {

    $routes->post('postLogin', 'Api\Protected\AuthenticationController::userLogin');
    $routes->get('getUserIndikator', 'Api\Protected\AuthenticationController::loadUserIndikator');
    $routes->put('putDataEntryNilai', 'Api\Protected\DataEntryController::updateDataEntryNilai');

    $routes->options('(:any)', static function ($any) {
        $response = service('response');
        return $response->setStatusCode(204)
                        ->setHeader('Access-Control-Allow-Origin', 'http://localhost')
                        ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                        ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    });
});