<?php

namespace App\Controllers\Api\Public;

use CodeIgniter\RESTful\ResourceController;
use App\Services\AuthService;

class AuthController extends ResourceController
{
    protected $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function superAdminLogin()
    {
        $data = $this->request->getJSON(true);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $result = $this->authService->login($username, $password);
        return $this->respond($result, $result['status']);
    }

    public function userAdminLogin()
    {
        $data = [
            'message' => 'User Admin.',
            'status' => 'SUCCESS',
        ];

        return $this->respond($data);
    }

    public function userOpLogin()
    {
        $data = [
            'message' => 'User Operator.',
            'status' => 'SUCCESS',
        ];

        return $this->respond($data);
    }
}
