<?php

namespace App\Controllers\Api\Protected;

use CodeIgniter\RESTful\ResourceController;
use App\Services\AuthenticationService;

class AuthenticationController extends ResourceController
{
    private $authenticationService;

    public function __construct() 
    {
        $this->authenticationService = new AuthenticationService();
    }

    public function userLogin()
    {
        $requestBody = $this->request->getJSON(true);

        // Validate payload
        if (!isset($requestBody['username']) || 
            !isset($requestBody['password']) ||
            !isset($requestBody['jenis'])) 
        {
            return $this->failValidationErrors('Missing username, password or jenis');
        }

        // Call login service
        $result = $this->authenticationService->login(
            $requestBody['username'],
            $requestBody['password'],
            $requestBody['jenis']
        );

        // Service returns staus = true/false
        if ($result['status'] === false) {
            return $this->respond([
                'status'  => 'error',
                'message' => $result['message'],
                'data'    => null
            ], 401); // unauthorized
        }

        // Login success
        return $this->respond([
            'status'  => 'success',
            'message' => $result['message'],
            'data'    => $result['data']
        ], 200);
    }

    public function loadUserIndikator()
    {
        $username = $this->request->getGet('username');
        $jenis    = $this->request->getGet('jenis');

        // Validate parameter
        if (!isset($username) || !isset($jenis)) 
        {
            return $this->failValidationErrors('Missing username or jenis');
        }

        // Call retrieveUserData service
        $result = $this->authenticationService->retrieveUserIndikator($username, $jenis);

        // Service returns staus = true/false
        if ($result['status'] === false) {
            return $this->respond([
                'status'  => 'error',
                'message' => $result['message'],
                'data'    => null
            ], 401);
        }

        // Login success
        return $this->respond([
            'status'  => 'success',
            'message' => $result['message'],
            'data'    => $result['data']
        ], 200);
    }
}
