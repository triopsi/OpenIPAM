<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserControllerTest extends TestCase
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
     * Test listing users.
     *
     * @return void
     */
    public function test_can_list_users()
    {
        User::factory()->count(3)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'language',
                        'gravatar_type',
                        'email_two_factor_enabled',
                        'email_verified_at',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);

        // Should include the test user + 3 additional users = 4 total
        $users = $response->json('data');
        $this->assertCount(4, $users);
    }

    /**
     * Test creating a user.
     *
     * @return void
     */
    public function test_can_create_user()
    {
        $userData = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'language' => 'en',
            'gravatar_type' => 'identicon',
            'email_two_factor_enabled' => false,
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/users', $userData);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'language' => 'en',
                'gravatar_type' => 'identicon',
                'email_two_factor_enabled' => false,
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'language' => 'en',
        ]);
    }

    /**
     * Test creating user with validation errors.
     *
     * @return void
     */
    public function test_cannot_create_user_with_invalid_data()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/users', [
                'name' => '', // Required
                'email' => 'invalid-email', // Invalid format
                'password' => '123', // Too short
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /**
     * Test creating user with duplicate email.
     *
     * @return void
     */
    public function test_cannot_create_user_with_duplicate_email()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/users', [
                'name' => 'New User',
                'email' => 'existing@example.com', // Duplicate
                'password' => 'password123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test showing a specific user.
     *
     * @return void
     */
    public function test_can_show_user()
    {
        $user = User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $user->id,
                'name' => 'John Smith',
                'email' => 'john@example.com',
            ]);
    }

    /**
     * Test showing non-existent user.
     *
     * @return void
     */
    public function test_cannot_show_nonexistent_user()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/users/999999');

        $response->assertStatus(404);
    }

    /**
     * Test updating a user.
     *
     * @return void
     */
    public function test_can_update_user()
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'language' => 'en',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'language' => 'de',
            'email_two_factor_enabled' => true,
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/users/{$user->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'language' => 'de',
                'email_two_factor_enabled' => true,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'language' => 'de',
            'email_two_factor_enabled' => true,
        ]);
    }

    /**
     * Test updating user password.
     *
     * @return void
     */
    public function test_can_update_user_password()
    {
        $user = User::factory()->create();
        $originalPassword = $user->password;

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/users/{$user->id}", [
                'password' => 'newpassword123',
            ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertNotEquals($originalPassword, $user->password);
    }

    /**
     * Test deleting a user.
     *
     * @return void
     */
    public function test_can_delete_user()
    {
        $user = User::factory()->create(['name' => 'Test User']);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => "User 'Test User' deleted successfully",
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    /**
     * Test cannot delete own account.
     *
     * @return void
     */
    public function test_cannot_delete_own_account()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/users/{$this->user->id}");

        $response->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'You cannot delete your own account',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
        ]);
    }

    /**
     * Test user deletion revokes API tokens.
     *
     * @return void
     */
    public function test_user_deletion_revokes_api_tokens()
    {
        $user = User::factory()->create();
        $token1 = $user->createToken('Token 1');
        $token2 = $user->createToken('Token 2');

        $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/users/{$user->id}");

        // Tokens should be deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token1->accessToken->id,
        ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token2->accessToken->id,
        ]);
    }

    /**
     * Test searching users.
     *
     * @return void
     */
    public function test_can_search_users()
    {
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        User::factory()->create(['name' => 'Bob Johnson', 'email' => 'bob@test.com']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/users?search=john');

        $response->assertStatus(200);

        $users = $response->json('data');
        $this->assertCount(2, $users); // John Doe and Bob Johnson
    }

    /**
     * Test filtering users by language.
     *
     * @return void
     */
    public function test_can_filter_users_by_language()
    {
        User::factory()->create(['language' => 'en']);
        User::factory()->create(['language' => 'de']);
        User::factory()->create(['language' => 'en']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/users?language=en');

        $response->assertStatus(200);

        $users = $response->json('data');
        // Should include test user + 2 created users with 'en' language = 3 total
        $this->assertCount(3, $users);

        foreach ($users as $user) {
            $this->assertEquals('en', $user['language']);
        }
    }

    /**
     * Test filtering users by 2FA status.
     *
     * @return void
     */
    public function test_can_filter_users_by_two_factor_status()
    {
        // Clear all existing users first
        User::query()->delete();

        // Create a fresh authenticated user without two-factor
        $authUser = User::factory()->create(['email_two_factor_enabled' => false]);

        // Create test users
        User::factory()->create(['email_two_factor_enabled' => true]);
        User::factory()->create(['email_two_factor_enabled' => false]);
        User::factory()->create(['email_two_factor_enabled' => true]);

        $response = $this->actingAs($authUser)->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/v1/users?email_two_factor_enabled=true');

        $response->assertStatus(200);

        $users = $response->json('data');
        $this->assertCount(2, $users);

        foreach ($users as $user) {
            $this->assertTrue($user['email_two_factor_enabled']);
        }
    }

    /**
     * Test user pagination.
     *
     * @return void
     */
    public function test_users_pagination()
    {
        User::factory()->count(25)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/users?per_page=10&page=1');

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
        $this->assertEquals(26, $meta['total']); // 25 + test user
    }

    /**
     * Test sorting users.
     *
     * @return void
     */
    public function test_can_sort_users()
    {
        User::factory()->create(['name' => 'Zebra User']);
        User::factory()->create(['name' => 'Alpha User']);

        // Sort by name ascending
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/users?sort_by=name&sort_direction=asc');

        $response->assertStatus(200);
        $users = $response->json('data');

        // Find the positions of our test users
        $alphaPosition = null;
        $zebraPosition = null;

        foreach ($users as $index => $user) {
            if ($user['name'] === 'Alpha User') {
                $alphaPosition = $index;
            }
            if ($user['name'] === 'Zebra User') {
                $zebraPosition = $index;
            }
        }

        $this->assertNotNull($alphaPosition);
        $this->assertNotNull($zebraPosition);
        $this->assertTrue($alphaPosition < $zebraPosition);
    }

    /**
     * Test unauthorized access.
     *
     * @return void
     */
    public function test_unauthorized_access_denied()
    {
        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(401);
    }

    /**
     * Test creating user with minimal data.
     *
     * @return void
     */
    public function test_can_create_user_with_minimal_data()
    {
        $userData = [
            'name' => 'Minimal User',
            'email' => 'minimal@example.com',
            'password' => 'password123',
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/users', $userData);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'Minimal User',
                'email' => 'minimal@example.com',
                'language' => 'en', // Default value
                'email_two_factor_enabled' => false, // Default value
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Minimal User',
            'email' => 'minimal@example.com',
            'language' => 'en',
            'email_two_factor_enabled' => false,
        ]);
    }
}
