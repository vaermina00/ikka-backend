<?php namespace App\Services;

use App\Repositories\SuperAdminRepository;
use Firebase\JWT\JWT;

class AuthService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new SuperAdminRepository();
    }

    public function login(string $username, string $password): array
    {
        $user = $this->repo->findByUsername($username);

        if (!$user || !password_verify($password, $user['password'])) {
            return ['status' => 401, 'message' => 'Invalid username or password'];
        }

        $payload = [
            'iss' => 'yourapp',
            'aud' => 'yourapp',
            'iat' => time(),
            'exp' => time() + 3600,
            'uid' => $user['id'],
            'role' => 'super_admin'
        ];

        $token = JWT::encode($payload, getenv('JWT_SECRET'), 'HS256');

        return [
            'status' => 200,
            'message' => 'Login successful',
            'token' => $token
        ];
    }
}
