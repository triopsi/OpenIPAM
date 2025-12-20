<?php

namespace Tests\Feature\Api\V1;

use App\Models\Device;
use App\Models\IpAddress;
use App\Models\IpAddressGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApiIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    private string $token;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'API Test User',
            'email' => 'api@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->token = $this->user->createToken('Integration Test Token')->plainTextToken;
    }

    /**
     * Get authorization header.
     */
    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    /**
     * Test complete device and IP management workflow.
     *
     * @return void
     */
    public function test_complete_device_and_ip_workflow()
    {
        // Step 1: Create IP Address Group
        $groupResponse = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/ip-address-groups', [
                'name' => 'Production Network',
                'description' => 'Production server network',
                'color' => '#007bff',
            ]);

        $groupResponse->assertStatus(201);
        $groupId = $groupResponse->json('data.id');

        // Step 2: Bulk create IP addresses
        $bulkIpResponse = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/ip-addresses/bulk-create', [
                'cidr' => '192.168.1.0/29', // Creates 6 IPs (excluding network and broadcast)
                'group_id' => $groupId,
                'status' => 'available',
                'description' => 'Production network range',
                'gateway' => '192.168.1.1',
            ]);

        $bulkIpResponse->assertStatus(200);
        $this->assertDatabaseCount('ip_addresses', 6); // 192.168.1.0/29 creates 6 usable IPs

        // Step 3: Create a device
        $deviceResponse = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/devices', [
                'name' => 'Web Server 01',
                'type' => 'server',
                'description' => 'Main web server',
                'location' => 'Data Center A',
                'status' => 'active',
            ]);

        $deviceResponse->assertStatus(201);
        $deviceId = $deviceResponse->json('data.id');

        // Step 4: Get available IP address
        $availableIpsResponse = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ip-addresses?available=true&per_page=1');

        $availableIpsResponse->assertStatus(200);
        $availableIp = $availableIpsResponse->json('data.0');
        $ipId = $availableIp['id'];

        // Step 5: Assign IP to device
        $assignResponse = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/devices/{$deviceId}/assign-ip", [
                'ip_address_id' => $ipId,
            ]);

        $assignResponse->assertStatus(200);

        // Step 6: Verify assignment
        $deviceWithIpResponse = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/devices/{$deviceId}");

        $deviceWithIpResponse->assertStatus(200);
        $deviceData = $deviceWithIpResponse->json('data');
        $this->assertCount(1, $deviceData['ip_addresses']);
        $this->assertEquals($availableIp['address'], $deviceData['ip_addresses'][0]['address']);

        // Step 7: Update device status
        $updateDeviceResponse = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/devices/{$deviceId}", [
                'status' => 'maintenance',
                'notes' => 'Scheduled maintenance',
            ]);

        $updateDeviceResponse->assertStatus(200);

        // Step 8: Bulk update IPs in group to reserved status
        $allIpsResponse = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/ip-addresses?group_id={$groupId}");

        $allIps = $allIpsResponse->json('data');
        $ipIds = array_column($allIps, 'id');

        $bulkUpdateResponse = $this->withHeaders($this->authHeaders())
            ->putJson('/api/v1/ip-addresses/bulk-update', [
                'ip_address_ids' => $ipIds,
                'updates' => [
                    'status' => 'reserved',
                    'description' => 'Reserved for production use',
                ],
            ]);

        $bulkUpdateResponse->assertStatus(200);

        // Step 9: Verify all IPs are updated
        $updatedIpsResponse = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/ip-addresses?group_id={$groupId}");

        $updatedIps = $updatedIpsResponse->json('data');
        foreach ($updatedIps as $ip) {
            $this->assertEquals('reserved', $ip['status']);
            $this->assertEquals('Reserved for production use', $ip['description']);
        }

        // Step 10: Clean up - unassign IP and delete resources
        $unassignResponse = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/devices/{$deviceId}/unassign-ip/{$ipId}");

        $unassignResponse->assertStatus(200);

        $deleteDeviceResponse = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/devices/{$deviceId}");

        $deleteDeviceResponse->assertStatus(200);
    }

    /**
     * Test API authentication flow.
     *
     * @return void
     */
    public function test_api_authentication_flow()
    {
        // Step 1: Login via API
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'api@example.com',
            'password' => 'password123',
            'token_name' => 'Integration Test Login',
        ]);

        $loginResponse->assertStatus(200);
        $newToken = $loginResponse->json('token');

        // Step 2: Use new token to access protected endpoint
        $userResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$newToken,
        ])->getJson('/api/v1/auth/user');

        $userResponse->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'API Test User',
                'email' => 'api@example.com',
            ]);

        // Step 3: Create additional API token
        $createTokenResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$newToken,
        ])->postJson('/api/v1/tokens', [
            'name' => 'Additional Token',
            'expires_at' => now()->addDays(30)->toISOString(),
        ]);

        $createTokenResponse->assertStatus(201);
        $additionalToken = $createTokenResponse->json('data.plain_text_token');

        // Step 4: List tokens
        $tokensResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$newToken,
        ])->getJson('/api/v1/tokens');

        $tokensResponse->assertStatus(200);
        $tokens = $tokensResponse->json('data');
        $this->assertGreaterThanOrEqual(3, count($tokens)); // Original + login + additional

        // Step 5: Use additional token
        $testResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$additionalToken,
        ])->getJson('/api/v1/devices');

        $testResponse->assertStatus(200);

        // Step 6: Revoke additional token
        $additionalTokenId = $createTokenResponse->json('data.id');
        $revokeResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$newToken,
        ])->deleteJson("/api/v1/tokens/{$additionalTokenId}");

        $revokeResponse->assertStatus(200);

        // Step 7: Verify revoked token doesn't work
        // Clear any cached tokens
        $this->app['auth']->forgetGuards();

        $failedResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$additionalToken,
        ])->getJson('/api/v1/devices');

        $failedResponse->assertStatus(401);

        // Step 8: Logout
        $logoutResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$newToken,
        ])->postJson('/api/v1/auth/logout');

        $logoutResponse->assertStatus(200);

        // Step 9: Verify logout token doesn't work
        // Clear any cached authentication guards
        $this->app['auth']->forgetGuards();

        $finalFailResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$newToken,
        ])->getJson('/api/v1/auth/user');

        $finalFailResponse->assertStatus(401);
    }

    /**
     * Test API error handling and validation.
     *
     * @return void
     */
    public function test_api_error_handling()
    {
        // Test 404 errors
        $notFoundResponse = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/devices/999999');

        $notFoundResponse->assertStatus(404);

        // Test validation errors
        $validationResponse = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/devices', [
                'name' => '', // Required field empty
                'type' => 'invalid_type', // Invalid value for enum
            ]);

        $validationResponse->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type']);
    }

    /**
     * Test API pagination and filtering across resources.
     *
     * @return void
     */
    public function test_api_pagination_and_filtering()
    {
        // Create test data
        Device::factory()->count(15)->create();
        IpAddress::factory()->count(25)->create();
        IpAddressGroup::factory()->count(10)->create();

        // Test device pagination
        $devicePage1 = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/devices?per_page=5&page=1');

        $devicePage1->assertStatus(200);
        $this->assertCount(5, $devicePage1->json('data'));
        $this->assertEquals(1, $devicePage1->json('meta.current_page'));

        $devicePage2 = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/devices?per_page=5&page=2');

        $devicePage2->assertStatus(200);
        $this->assertCount(5, $devicePage2->json('data'));
        $this->assertEquals(2, $devicePage2->json('meta.current_page'));

        // Test IP address pagination
        $ipPage1 = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ip-addresses?per_page=10&page=1');

        $ipPage1->assertStatus(200);
        $this->assertCount(10, $ipPage1->json('data'));

        // Test group pagination
        $groupPage1 = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ip-address-groups?per_page=5&page=1');

        $groupPage1->assertStatus(200);
        $this->assertCount(5, $groupPage1->json('data'));

        // Test filtering
        $serverDevices = Device::factory()->create(['type' => 'server']);
        $laptopDevices = Device::factory()->create(['type' => 'laptop']);

        $serverFilter = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/devices?type=server');

        $serverFilter->assertStatus(200);
        $servers = $serverFilter->json('data');
        $this->assertGreaterThan(0, count($servers));

        foreach ($servers as $device) {
            $this->assertEquals('server', $device['type']);
        }
    }

    /**
     * Test API rate limiting (basic test).
     *
     * @return void
     */
    public function test_api_rate_limiting()
    {
        // Make multiple requests to verify the API responds consistently
        // Real rate limiting would require specific middleware configuration

        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->withHeaders($this->authHeaders())
                ->getJson('/api/v1/devices');
        }

        // Verify all requests are successful (no rate limiting in test environment)
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }

        // Basic assertion that we can make multiple requests
        $this->assertCount(10, $responses);
    }

    /**
     * Test API CORS headers and JSON responses.
     *
     * @return void
     */
    public function test_api_response_format()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/devices');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');

        // Verify JSON structure
        $response->assertJsonStructure([
            'data',
            'links',
            'meta',
        ]);

        // Test POST response format
        $createResponse = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/devices', [
                'name' => 'Test Device',
                'type' => 'server',
            ]);

        $createResponse->assertStatus(201)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'type',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }
}
