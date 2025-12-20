<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnauthorizedAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test unauthorized access to protected API routes.
     */
    public function test_unauthorized_access_to_protected_routes()
    {
        // Test various protected endpoints without authentication
        $protectedRoutes = [
            'GET /api/v1/devices',
            'POST /api/v1/devices',
            'GET /api/v1/ip-addresses',
            'POST /api/v1/ip-addresses',
            'GET /api/v1/users',
            'POST /api/v1/users',
            'GET /api/v1/tokens',
            'POST /api/v1/tokens',
        ];

        foreach ($protectedRoutes as $route) {
            [$method, $path] = explode(' ', $route);

            if ($method === 'GET') {
                $response = $this->getJson($path);
            } else {
                $response = $this->postJson($path, []);
            }

            $response->assertStatus(401, "Failed for {$route}");
        }
    }

    /**
     * Test invalid token access.
     */
    public function test_invalid_token_access()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token-123',
        ])->getJson('/api/v1/devices');

        $response->assertStatus(401);
    }
}
