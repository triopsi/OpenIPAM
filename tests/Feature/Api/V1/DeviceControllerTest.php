<?php

namespace Tests\Feature\Api\V1;

use App\Models\Device;
use App\Models\IpAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DeviceControllerTest extends TestCase
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

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('Test Token')->plainTextToken;
    }

    /**
     * Get authorization header.
     */
    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    /**
     * Test listing devices.
     *
     * @return void
     */
    public function test_can_list_devices()
    {
        Device::factory()->count(5)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/devices');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                        'description',
                        'location',
                        'status',
                        'is_active',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    /**
     * Test creating a device.
     *
     * @return void
     */
    public function test_can_create_device()
    {
        $deviceData = [
            'name' => 'Test Server',
            'type' => 'server',
            'description' => 'Test server description',
            'location' => 'Data Center A',
            'hostname' => 'test-server-01',
            'mac_address' => '00:11:22:33:44:55',
            'status' => 'active',
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/devices', $deviceData);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'Test Server',
                'type' => 'server',
                'location' => 'Data Center A',
            ]);

        $this->assertDatabaseHas('devices', [
            'name' => 'Test Server',
            'type' => 'server',
            'hostname' => 'test-server-01',
        ]);
    }

    /**
     * Test creating device with validation errors.
     *
     * @return void
     */
    public function test_cannot_create_device_with_invalid_data()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/devices', [
                'name' => '', // Required field
                'type' => 'invalid_type', // Invalid enum value
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type']);
    }

    /**
     * Test showing a specific device.
     *
     * @return void
     */
    public function test_can_show_device()
    {
        $device = Device::factory()->create([
            'name' => 'Test Device',
            'type' => 'laptop',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/devices/{$device->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $device->id,
                'name' => 'Test Device',
                'type' => 'laptop',
            ]);
    }

    /**
     * Test showing non-existent device.
     *
     * @return void
     */
    public function test_cannot_show_nonexistent_device()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/devices/999999');

        $response->assertStatus(404);
    }

    /**
     * Test updating a device.
     *
     * @return void
     */
    public function test_can_update_device()
    {
        $device = Device::factory()->create([
            'name' => 'Original Name',
            'status' => 'active',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'status' => 'maintenance',
            'description' => 'Device is under maintenance',
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/devices/{$device->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Name',
                'status' => 'maintenance',
                'description' => 'Device is under maintenance',
            ]);

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'name' => 'Updated Name',
            'status' => 'maintenance',
        ]);
    }

    /**
     * Test deleting a device.
     *
     * @return void
     */
    public function test_can_delete_device()
    {
        $device = Device::factory()->create(['name' => 'Test Device']);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/devices/{$device->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => "Device 'Test Device' deleted successfully",
            ]);

        $this->assertDatabaseMissing('devices', [
            'id' => $device->id,
        ]);
    }

    /**
     * Test filtering devices by type.
     *
     * @return void
     */
    public function test_can_filter_devices_by_type()
    {
        Device::factory()->create(['type' => 'server']);
        Device::factory()->create(['type' => 'laptop']);
        Device::factory()->create(['type' => 'server']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/devices?type=server');

        $response->assertStatus(200);

        $devices = $response->json('data');
        $this->assertCount(2, $devices);

        foreach ($devices as $device) {
            $this->assertEquals('server', $device['type']);
        }
    }

    /**
     * Test searching devices.
     *
     * @return void
     */
    public function test_can_search_devices()
    {
        Device::factory()->create(['name' => 'Web Server']);
        Device::factory()->create(['name' => 'Database Server']);
        Device::factory()->create(['name' => 'Laptop']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/devices?search=server');

        $response->assertStatus(200);

        $devices = $response->json('data');
        $this->assertCount(2, $devices);
    }

    /**
     * Test device pagination.
     *
     * @return void
     */
    public function test_devices_pagination()
    {
        Device::factory()->count(25)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/devices?per_page=10&page=1');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);

        $meta = $response->json('meta');
        $this->assertEquals(1, $meta['current_page']);
        $this->assertEquals(10, $meta['per_page']);
        $this->assertEquals(25, $meta['total']);
    }

    /**
     * Test assigning IP to device.
     *
     * @return void
     */
    public function test_can_assign_ip_to_device()
    {
        $device = Device::factory()->create(['name' => 'Test Device']);
        $ipAddress = IpAddress::factory()->create([
            'ip_address' => '192.168.1.100',
            'status' => 'available',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/devices/{$device->id}/assign-ip", [
                'ip_address_id' => $ipAddress->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'IP address assigned successfully',
            ]);

        $this->assertDatabaseHas('device_ip_address', [
            'device_id' => $device->id,
            'ip_address_id' => $ipAddress->id,
        ]);
    }

    /**
     * Test unassigning IP from device.
     *
     * @return void
     */
    public function test_can_unassign_ip_from_device()
    {
        $device = Device::factory()->create(['name' => 'Test Device']);
        $ipAddress = IpAddress::factory()->create([
            'ip_address' => '192.168.1.100',
            'status' => 'available',
        ]);

        // Assign IP first
        $device->ipAddresses()->attach($ipAddress->id);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/devices/{$device->id}/unassign-ip/{$ipAddress->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'IP address unassigned successfully',
            ]);

        $this->assertDatabaseMissing('device_ip_address', [
            'device_id' => $device->id,
            'ip_address_id' => $ipAddress->id,
        ]);
    }

    /**
     * Test unauthorized access.
     *
     * @return void
     */
    public function test_unauthorized_access_denied()
    {
        $response = $this->getJson('/api/v1/devices');

        $response->assertStatus(401);
    }

    /**
     * Test device sorting.
     *
     * @return void
     */
    public function test_can_sort_devices()
    {
        Device::factory()->create(['name' => 'Zebra Device', 'created_at' => now()->subDay()]);
        Device::factory()->create(['name' => 'Alpha Device', 'created_at' => now()]);

        // Sort by name ascending
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/devices?sort_by=name&sort_direction=asc');

        $response->assertStatus(200);
        $devices = $response->json('data');
        $this->assertEquals('Alpha Device', $devices[0]['name']);

        // Sort by name descending
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/devices?sort_by=name&sort_direction=desc');

        $response->assertStatus(200);
        $devices = $response->json('data');
        $this->assertEquals('Zebra Device', $devices[0]['name']);
    }
}
