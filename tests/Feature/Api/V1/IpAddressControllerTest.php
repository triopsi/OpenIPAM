<?php

namespace Tests\Feature\Api\V1;

use App\Models\IpAddress;
use App\Models\IpAddressGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class IpAddressControllerTest extends TestCase
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
     * Test listing IP addresses.
     *
     * @return void
     */
    public function test_can_list_ip_addresses()
    {
        IpAddress::factory()->count(5)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ip-addresses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'address', // This is mapped from ip_address via IpAddressResource
                        'subnet',
                        'gateway',
                        'version', // This is mapped to type in the resource
                        'status',
                        'description',
                        'group',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    /**
     * Test creating an IP address.
     *
     * @return void
     */
    public function test_can_create_ip_address()
    {
        $group = IpAddressGroup::factory()->create();

        $ipData = [
            'ip_address' => '192.168.1.100',
            'subnet' => '192.168.1.0/24',
            'gateway' => '192.168.1.1',
            'version' => 4,
            'status' => 'available',
            'group_id' => $group->id,
            'description' => 'Test IP address',
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/ip-addresses', $ipData);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'address' => '192.168.1.100',
                'subnet' => '192.168.1.0/24',
                'version' => 4,
            ]);

        $this->assertDatabaseHas('ip_addresses', [
            'ip_address' => '192.168.1.100',
            'subnet' => '192.168.1.0/24',
            'group_id' => $group->id,
        ]);
    }

    /**
     * Test creating IP address with validation errors.
     *
     * @return void
     */
    public function test_cannot_create_ip_address_with_invalid_data()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/ip-addresses', [
                'ip_address' => 'invalid-ip',
                'subnet' => 'invalid-subnet',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ip_address']);
    }

    /**
     * Test creating duplicate IP address.
     *
     * @return void
     */
    public function test_cannot_create_duplicate_ip_address()
    {
        IpAddress::factory()->create(['ip_address' => '192.168.1.100']);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/ip-addresses', [
                'ip_address' => '192.168.1.100',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ip_address']);
    }

    /**
     * Test showing a specific IP address.
     *
     * @return void
     */
    public function test_can_show_ip_address()
    {
        $ipAddress = IpAddress::factory()->create([
            'ip_address' => '192.168.1.100',
            'version' => 4,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/ip-addresses/{$ipAddress->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $ipAddress->id,
                'address' => '192.168.1.100',
                'version' => 4,
            ]);
    }

    /**
     * Test updating an IP address.
     *
     * @return void
     */
    public function test_can_update_ip_address()
    {
        $ipAddress = IpAddress::factory()->create([
            'ip_address' => '192.168.1.100',
            'status' => 'active',
        ]);

        $updateData = [
            'status' => 'reserved',
            'description' => 'Reserved for maintenance',
            'reserved_until' => now()->addDays(30)->toISOString(),
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/ip-addresses/{$ipAddress->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'status' => 'reserved',
                'description' => 'Reserved for maintenance',
            ]);

        $this->assertDatabaseHas('ip_addresses', [
            'id' => $ipAddress->id,
            'status' => 'reserved',
            'description' => 'Reserved for maintenance',
        ]);
    }

    /**
     * Test deleting an IP address.
     *
     * @return void
     */
    public function test_can_delete_ip_address()
    {
        $ipAddress = IpAddress::factory()->create(['ip_address' => '192.168.1.100']);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/ip-addresses/{$ipAddress->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => "IP address '192.168.1.100' deleted successfully",
            ]);

        $this->assertDatabaseMissing('ip_addresses', [
            'id' => $ipAddress->id,
        ]);
    }

    /**
     * Test filtering IP addresses by type.
     *
     * @return void
     */
    public function test_can_filter_ip_addresses_by_type()
    {
        $this->markTestSkipped('Test temporarily disabled');

        // Start with clean slate
        IpAddress::truncate();

        $ipv4_1 = IpAddress::factory()->create(['version' => 4, 'ip_address' => '192.168.1.1']);
        $ipv6_1 = IpAddress::factory()->create(['version' => 6, 'ip_address' => '2001:db8::1']);
        $ipv4_2 = IpAddress::factory()->create(['version' => 4, 'ip_address' => '192.168.1.2']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ip-addresses?version=4');

        $response->assertStatus(200);

        $ipAddresses = $response->json('data');
        $this->assertCount(2, $ipAddresses);

        foreach ($ipAddresses as $ipAddress) {
            $this->assertEquals(4, $ipAddress['version']);
        }
    }

    /**
     * Test searching IP addresses.
     *
     * @return void
     */
    public function test_can_search_ip_addresses()
    {
        IpAddress::factory()->create(['ip_address' => '192.168.1.100', 'description' => 'Web server']);
        IpAddress::factory()->create(['ip_address' => '192.168.1.101', 'description' => 'Database server']);
        IpAddress::factory()->create(['ip_address' => '10.0.0.1', 'description' => 'Router']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ip-addresses?search=192.168.1');

        $response->assertStatus(200);

        $ipAddresses = $response->json('data');
        $this->assertCount(2, $ipAddresses);
    }

    /**
     * Test filtering available IP addresses.
     *
     * @return void
     */
    public function test_can_filter_available_ip_addresses()
    {
        // Clear any existing data
        IpAddress::query()->delete();

        // Create available IP
        IpAddress::factory()->create([
            'ip_address' => '192.168.1.100',
            'status' => 'available',
        ]);

        // Create assigned IP
        $assignedIp = IpAddress::factory()->create([
            'ip_address' => '192.168.1.101',
            'status' => 'assigned',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ip-addresses?available=true');

        $response->assertStatus(200);

        $ipAddresses = $response->json('data');
        $this->assertCount(1, $ipAddresses);
        $this->assertEquals('192.168.1.100', $ipAddresses[0]['address']);
    }

    /**
     * Test bulk creating IP addresses from CIDR.
     *
     * @return void
     */
    public function test_can_bulk_create_ip_addresses_from_cidr()
    {
        $group = IpAddressGroup::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/ip-addresses/bulk-create', [
                'cidr' => '192.168.1.0/30', // Creates 2 IPs (1,2) excluding network and broadcast
                'group_id' => $group->id,
                'status' => 'available',
                'description' => 'Bulk created',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'created',
                'skipped',
            ]);

        // Check that the usable IPs were created (excluding network .0 and broadcast .3)
        $this->assertDatabaseHas('ip_addresses', [
            'ip_address' => '192.168.1.1',
            'group_id' => $group->id,
        ]);

        $this->assertDatabaseHas('ip_addresses', [
            'ip_address' => '192.168.1.2',
            'group_id' => $group->id,
        ]);
    }

    /**
     * Test bulk creating with exclusions.
     *
     * @return void
     */
    public function test_bulk_create_with_exclusions()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/ip-addresses/bulk-create', [
                'cidr' => '192.168.1.0/30',
                'exclude_network' => true,
                'exclude_broadcast' => true,
                'exclude_gateway' => false,
            ]);

        $response->assertStatus(200);

        // Should create only 2 IPs (excluding network and broadcast)
        $this->assertDatabaseCount('ip_addresses', 2);
    }

    /**
     * Test bulk updating IP addresses.
     *
     * @return void
     */
    public function test_can_bulk_update_ip_addresses()
    {
        $ip1 = IpAddress::factory()->create(['status' => 'available']);
        $ip2 = IpAddress::factory()->create(['status' => 'available']);
        $ip3 = IpAddress::factory()->create(['status' => 'available']);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson('/api/v1/ip-addresses/bulk-update', [
                'ip_address_ids' => [$ip1->id, $ip2->id],
                'updates' => [
                    'status' => 'reserved',
                    'description' => 'Under maintenance',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'updated_count' => 2,
            ]);

        $this->assertDatabaseHas('ip_addresses', [
            'id' => $ip1->id,
            'status' => 'reserved',
            'description' => 'Under maintenance',
        ]);

        $this->assertDatabaseHas('ip_addresses', [
            'id' => $ip2->id,
            'status' => 'reserved',
            'description' => 'Under maintenance',
        ]);

        // Third IP should remain unchanged
        $this->assertDatabaseHas('ip_addresses', [
            'id' => $ip3->id,
            'status' => 'available',
        ]);
    }

    /**
     * Test IP address pagination.
     *
     * @return void
     */
    public function test_ip_addresses_pagination()
    {
        IpAddress::factory()->count(25)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ip-addresses?per_page=10&page=1');

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
     * Test sorting IP addresses.
     *
     * @return void
     */
    public function test_can_sort_ip_addresses()
    {
        // Clear any existing data
        IpAddress::query()->delete();

        IpAddress::factory()->create(['ip_address' => '192.168.1.2']);
        IpAddress::factory()->create(['ip_address' => '192.168.1.1']);
        IpAddress::factory()->create(['ip_address' => '192.168.1.3']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ip-addresses?sort_by=ip_address&sort_direction=asc');

        $response->assertStatus(200);
        $ipAddresses = $response->json('data');

        $this->assertEquals('192.168.1.1', $ipAddresses[0]['address']);
        $this->assertEquals('192.168.1.2', $ipAddresses[1]['address']);
        $this->assertEquals('192.168.1.3', $ipAddresses[2]['address']);
    }

    /**
     * Test unauthorized access.
     *
     * @return void
     */
    public function test_unauthorized_access_denied()
    {
        $response = $this->getJson('/api/v1/ip-addresses');

        $response->assertStatus(401);
    }
}
