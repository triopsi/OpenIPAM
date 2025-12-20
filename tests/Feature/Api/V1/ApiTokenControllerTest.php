<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ApiTokenControllerTest extends TestCase
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
     * Test listing user's API tokens.
     *
     * @return void
     */
    public function test_can_list_user_api_tokens()
    {
        // Create additional tokens
        $this->user->createToken('Token 1');
        $this->user->createToken('Token 2');

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/tokens');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'abilities',
                        'expires_at',
                        'created_at',
                        'last_used_at',
                        'is_expired',
                    ],
                ],
            ]);

        // Should include the test token + 2 additional tokens = 3 total
        $tokens = $response->json('data');
        $this->assertCount(3, $tokens);
    }

    /**
     * Test creating a new API token.
     *
     * @return void
     */
    public function test_can_create_api_token()
    {
        $tokenData = [
            'name' => 'New API Token',
            'abilities' => ['*'],
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/tokens', $tokenData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'abilities',
                    'expires_at',
                    'created_at',
                    'plain_text_token',
                ],
            ])
            ->assertJsonFragment([
                'name' => 'New API Token',
                'abilities' => ['*'],
            ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'name' => 'New API Token',
        ]);
    }

    /**
     * Test creating token with custom expiration.
     *
     * @return void
     */
    public function test_can_create_token_with_custom_expiration()
    {
        $expiresAt = now()->addDays(30)->toISOString();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/tokens', [
                'name' => 'Custom Expiry Token',
                'expires_at' => $expiresAt,
            ]);

        $response->assertStatus(201);

        $tokenId = $response->json('data.id');
        $token = PersonalAccessToken::find($tokenId);

        $this->assertNotNull($token->expires_at);
        $this->assertEquals(
            Carbon::parse($expiresAt)->format('Y-m-d'),
            $token->expires_at->format('Y-m-d')
        );
    }

    /**
     * Test creating never expiring token.
     *
     * @return void
     */
    public function test_can_create_never_expiring_token()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/tokens', [
                'name' => 'Never Expires Token',
                'never_expires' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'expires_at' => null,
            ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'name' => 'Never Expires Token',
            'expires_at' => null,
        ]);
    }

    /**
     * Test creating token with custom abilities.
     *
     * @return void
     */
    public function test_can_create_token_with_custom_abilities()
    {
        $abilities = ['read', 'write'];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/tokens', [
                'name' => 'Limited Token',
                'abilities' => $abilities,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'abilities' => $abilities,
            ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'name' => 'Limited Token',
        ]);
    }

    /**
     * Test token validation errors.
     *
     * @return void
     */
    public function test_cannot_create_token_with_invalid_data()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/tokens', [
                'name' => '', // Required
                'expires_at' => 'invalid-date',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'expires_at']);
    }

    /**
     * Test token expiration validation.
     *
     * @return void
     */
    public function test_cannot_create_token_with_past_expiration()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/tokens', [
                'name' => 'Past Token',
                'expires_at' => now()->subDay()->toISOString(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['expires_at']);
    }

    /**
     * Test revoking an API token.
     *
     * @return void
     */
    public function test_can_revoke_api_token()
    {
        $newToken = $this->user->createToken('Token to Revoke');

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/tokens/{$newToken->accessToken->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => "API token 'Token to Revoke' revoked successfully",
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $newToken->accessToken->id,
        ]);
    }

    /**
     * Test revoking non-existent token.
     *
     * @return void
     */
    public function test_cannot_revoke_nonexistent_token()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson('/api/v1/tokens/999999');

        $response->assertStatus(404);
    }

    /**
     * Test cannot revoke another user's token.
     *
     * @return void
     */
    public function test_cannot_revoke_another_users_token()
    {
        $otherUser = User::factory()->create();
        $otherToken = $otherUser->createToken('Other User Token');

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/tokens/{$otherToken->accessToken->id}");

        $response->assertStatus(404);

        // Token should still exist
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $otherToken->accessToken->id,
        ]);
    }

    /**
     * Test token creation with default expiration.
     *
     * @return void
     */
    public function test_token_has_default_expiration()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/tokens', [
                'name' => 'Default Expiry Token',
            ]);

        $response->assertStatus(201);

        $tokenId = $response->json('data.id');
        $token = PersonalAccessToken::find($tokenId);

        $this->assertNotNull($token->expires_at);

        // Should expire in approximately 1 year (within a day tolerance)
        $expectedExpiry = now()->addYear();
        $actualExpiry = $token->expires_at;

        $this->assertTrue(
            $actualExpiry->diffInDays($expectedExpiry) <= 1,
            'Token should expire in approximately 1 year'
        );
    }

    /**
     * Test unauthorized access to token endpoints.
     *
     * @return void
     */
    public function test_unauthorized_access_denied()
    {
        $response = $this->getJson('/api/v1/tokens');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/tokens', [
            'name' => 'Test Token',
        ]);
        $response->assertStatus(401);

        $response = $this->deleteJson('/api/v1/tokens/1');
        $response->assertStatus(401);
    }

    /**
     * Test token response includes plain text token.
     *
     * @return void
     */
    public function test_token_creation_response_includes_plain_text()
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/tokens', [
                'name' => 'Test Token',
            ]);

        $response->assertStatus(201);

        $plainTextToken = $response->json('data.plain_text_token');
        $this->assertNotNull($plainTextToken);
        $this->assertIsString($plainTextToken);
        $this->assertTrue(strlen($plainTextToken) > 0);
    }

    /**
     * Test token list does not include plain text tokens.
     *
     * @return void
     */
    public function test_token_list_does_not_include_plain_text()
    {
        $this->user->createToken('Another Token');

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/tokens');

        $response->assertStatus(200);

        $tokens = $response->json('data');
        foreach ($tokens as $token) {
            $this->assertArrayNotHasKey('plain_text_token', $token);
        }
    }

    /**
     * Test token expiry status.
     *
     * @return void
     */
    public function test_token_shows_expiry_status()
    {
        // Create expired token
        $expiredToken = $this->user->createToken('Expired Token', ['*'], now()->subDay());

        // Create active token
        $activeToken = $this->user->createToken('Active Token', ['*'], now()->addDay());

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/tokens');

        $response->assertStatus(200);

        $tokens = $response->json('data');

        // Find tokens and check expiry status
        foreach ($tokens as $token) {
            if ($token['name'] === 'Expired Token') {
                $this->assertTrue($token['is_expired']);
            } elseif ($token['name'] === 'Active Token') {
                $this->assertFalse($token['is_expired']);
            }
        }
    }
}
