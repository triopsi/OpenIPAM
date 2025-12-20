<?php

namespace Tests\Feature\Api\V1;

use App\Models\IpAddressGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class IpAddressGroupControllerTest extends TestCase
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
     * Test listing IP address groups.
     *
     * @return void
     */
    public function test_can_list_ip_address_groups()
    {
        IpAddressGroup::factory()->count(5)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ip-address-groups');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'color',
                        'is_active',
                        'created_at',
                        'updated_at',
                        'ip_addresses_count',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    /**
     * Test creating an IP address group.
     *
     * @return void
     */
    public function test_can_create_ip_address_group()
    {
        $groupData = [
            'name' => 'Production Servers',
            'description' => 'IP range for production servers',
            'color' => '#007bff',
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/ip-address-groups', $groupData);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'Production Servers',
                'description' => 'IP range for production servers',
                'color' => '#007bff',
            ]);

        $this->assertDatabaseHas('ip_address_groups', [
            'name' => 'Production Servers',
            'color' => '#007bff',
        ]);
    }

    /**
     * Test creating group with validation errors.
     *
     * @return void
     */
    public function test_cannot_create_group_with_invalid_data()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/ip-address-groups', [
                'name' => '', // Required field
                'color' => 'invalid-color',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test showing a specific IP address group.
     *
     * @return void
     */
    public function test_can_show_ip_address_group()
    {
        $group = IpAddressGroup::factory()->create([
            'name' => 'Test Group',
            'description' => 'Test description',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/ip-address-groups/{$group->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $group->id,
                'name' => 'Test Group',
                'description' => 'Test description',
            ]);
    }

    /**
     * Test showing non-existent group.
     *
     * @return void
     */
    public function test_cannot_show_nonexistent_group()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ip-address-groups/999999');

        $response->assertStatus(404);
    }

    /**
     * Test updating an IP address group.
     *
     * @return void
     */
    public function test_can_update_ip_address_group()
    {
        $group = IpAddressGroup::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original description',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'color' => '#28a745',
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/ip-address-groups/{$group->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Name',
                'description' => 'Updated description',
                'color' => '#28a745',
            ]);

        $this->assertDatabaseHas('ip_address_groups', [
            'id' => $group->id,
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]);
    }

    /**
     * Test deleting an IP address group.
     *
     * @return void
     */
    public function test_can_delete_ip_address_group()
    {
        $group = IpAddressGroup::factory()->create(['name' => 'Test Group']);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/ip-address-groups/{$group->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => "IP address group 'Test Group' deleted successfully",
            ]);

        $this->assertDatabaseMissing('ip_address_groups', [
            'id' => $group->id,
        ]);
    }

    /**
     * Test searching IP address groups.
     *
     * @return void
     */
    public function test_can_search_ip_address_groups()
    {
        IpAddressGroup::factory()->create(['name' => 'Production Servers']);
        IpAddressGroup::factory()->create(['name' => 'Development Servers']);
        IpAddressGroup::factory()->create(['name' => 'Network Equipment']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ip-address-groups?search=server');

        $response->assertStatus(200);

        $groups = $response->json('data');
        $this->assertCount(2, $groups);
    }

    /**
     * Test group pagination.
     *
     * @return void
     */
    public function test_ip_address_groups_pagination()
    {
        IpAddressGroup::factory()->count(25)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ip-address-groups?per_page=10&page=1');

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
     * Test sorting IP address groups.
     *
     * @return void
     */
    public function test_can_sort_ip_address_groups()
    {
        IpAddressGroup::factory()->create(['name' => 'Zebra Group', 'created_at' => now()->subDay()]);
        IpAddressGroup::factory()->create(['name' => 'Alpha Group', 'created_at' => now()]);

        // Sort by name ascending
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ip-address-groups?sort_by=name&sort_direction=asc');

        $response->assertStatus(200);
        $groups = $response->json('data');
        $this->assertEquals('Alpha Group', $groups[0]['name']);

        // Sort by name descending
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/ip-address-groups?sort_by=name&sort_direction=desc');

        $response->assertStatus(200);
        $groups = $response->json('data');
        $this->assertEquals('Zebra Group', $groups[0]['name']);
    }

    /**
     * Test group with IP addresses count.
     *
     * @return void
     */
    public function test_group_shows_ip_addresses_count()
    {
        $group = IpAddressGroup::factory()->create(['name' => 'Test Group']);

        // Create IP addresses in this group
        \App\Models\IpAddress::factory()->count(3)->create(['group_id' => $group->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/ip-address-groups/{$group->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'ip_addresses_count' => 3,
            ]);
    }

    /**
     * Test unauthorized access.
     *
     * @return void
     */
    public function test_unauthorized_access_denied()
    {
        $response = $this->getJson('/api/v1/ip-address-groups');

        $response->assertStatus(401);
    }

    /**
     * Test creating group with only required field.
     *
     * @return void
     */
    public function test_can_create_group_with_minimal_data()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/ip-address-groups', [
                'name' => 'Minimal Group',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'Minimal Group',
            ]);

        $this->assertDatabaseHas('ip_address_groups', [
            'name' => 'Minimal Group',
        ]);
    }

    /**
     * Test updating group with partial data.
     *
     * @return void
     */
    public function test_can_partially_update_group()
    {
        $group = IpAddressGroup::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original description',
            'color' => '#000000',
        ]);

        // Update only the name
        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/ip-address-groups/{$group->id}", [
                'name' => 'Updated Name Only',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ip_address_groups', [
            'id' => $group->id,
            'name' => 'Updated Name Only',
            'description' => 'Original description', // Should remain unchanged
            'color' => '#000000', // Should remain unchanged
        ]);
    }
}
