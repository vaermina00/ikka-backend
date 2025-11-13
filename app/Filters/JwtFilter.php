<?php namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return service('response')->setJSON(['status' => '401', 'message' => 'Unauthorized.'])->setStatusCode(401);
        }

        $token = $matches[1];

        try {
            $decoded = JWT::decode($token, new Key(getenv('JWT_SECRET'), 'HS256'));
            $request->userData = $decoded;
        } catch (\Exception $e) {
            return service('response')->setJSON(['message' => 'Invalid or expired token'])->setStatusCode(401);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
