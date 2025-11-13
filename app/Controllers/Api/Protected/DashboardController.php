<?php

namespace App\Controllers\Api\Protected;

use App\Repositories\TestRepository;
use App\Services\TestService;
use CodeIgniter\RESTful\ResourceController;
use Config\Database;

class DashboardController extends ResourceController
{
    private $testService;

    public function __construct() 
    {
        $db = Database::connect();
        $repository = new TestRepository($db);
        $this->testService = new TestService($repository);
    }

    // public function test()
    // {
    //     try {
    //         $data = $this->testService->fetchAll();

    //         return $this->respond([
    //             'status'  => 'SUCCESS',
    //             'message' => 'Data retrieved successfully',
    //             'data'    => $data,
    //         ]);
    //     } catch (\Throwable $e) {
    //         return $this->failServerError($e->getMessage());
    //     }
    // }

    public function test()
    {
        // $decoded = $this->request->userData ?? null;

        // return $this->respond([
        //     'message' => 'Authorized access',
        //     'user' => $decoded
        // ]);

        try {
            $data = $this->testService->fetchAll();

            return $this->respond([
                'status'  => 'SUCCESS',
                'data'    => $data,
            ]);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
