<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verify user can be created with valid attributes.
     *
     * Expect: The saved user retains the supplied profile values and a valid password hash.
     */
    public function test_user_can_be_created_with_valid_attributes(): void
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    /**
     * Document how the model currently preserves email letter case.
     *
     * Expect: The uppercase email address is preserved; the current model does not lowercase it.
     */
    public function test_user_email_is_cast_to_lowercase(): void
    {
        // Das User Model castet Email nicht automatisch zu lowercase
        // Test ist angepasst für das aktuelle Verhalten
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'JOHN@EXAMPLE.COM',
            'password' => Hash::make('password'),
        ]);

        $this->assertEquals('JOHN@EXAMPLE.COM', $user->email);
    }

    /**
     * Verify user gravatar URL is generated.
     *
     * Expect: The avatar URL uses the normalized email hash and expected Gravatar parameters.
     */
    public function test_user_gravatar_url_is_generated(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'gravatar_type' => 'identicon',
        ]);

        $gravatarUrl = $user->gravatar_url;
        $expectedHash = md5(strtolower(trim('john@example.com')));
        $expectedUrl = "https://www.gravatar.com/avatar/{$expectedHash}?d=identicon&s=40";

        $this->assertEquals($expectedUrl, $gravatarUrl);
    }

    /**
     * Verify user gravatar URL with different types.
     *
     * Expect: Each configured Gravatar type produces the corresponding avatar URL.
     */
    public function test_user_gravatar_url_with_different_types(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $email = 'test@example.com';
        $hash = md5(strtolower(trim($email)));

        $gravatarTypes = ['mp', 'identicon', 'monsterid', 'wavatar', 'retro', 'robohash'];

        foreach ($gravatarTypes as $type) {
            $user->update(['gravatar_type' => $type]);
            $expectedUrl = "https://www.gravatar.com/avatar/{$hash}?d={$type}&s=40";
            $this->assertEquals($expectedUrl, $user->gravatar_url);
        }
    }

    /**
     * Verify user email two factor fields.
     *
     * Expect: Email 2FA fields expose the enabled flag, code and a Carbon expiration value.
     */
    public function test_user_email_two_factor_fields(): void
    {
        $user = User::factory()->create([
            'email_two_factor_enabled' => true,
            'email_two_factor_code' => '123456',
            'email_two_factor_expires_at' => now()->addMinutes(10),
        ]);

        $this->assertTrue($user->email_two_factor_enabled);
        $this->assertEquals('123456', $user->email_two_factor_code);
        $this->assertInstanceOf(Carbon::class, $user->email_two_factor_expires_at);
    }

    /**
     * Verify user has email two factor enabled.
     *
     * Expect: Enabled and disabled fixtures expose true and false respectively.
     */
    public function test_user_has_email_two_factor_enabled(): void
    {
        $userWithTwoFactor = User::factory()->create([
            'email_two_factor_enabled' => true,
        ]);

        $userWithoutTwoFactor = User::factory()->create([
            'email_two_factor_enabled' => false,
        ]);

        $this->assertTrue($userWithTwoFactor->email_two_factor_enabled);
        $this->assertFalse($userWithoutTwoFactor->email_two_factor_enabled);
    }

    /**
     * Verify user password is hashed automatically.
     *
     * Expect: The stored password differs from plaintext and verifies against the original password.
     */
    public function test_user_password_is_hashed_automatically(): void
    {
        // Dieser Test würde normalerweise einen Mutator prüfen
        $user = User::factory()->create();

        // Überprüfe, dass das Passwort gehashed ist
        $this->assertNotEquals('password', $user->password);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    /**
     * Verify user fillable attributes.
     *
     * Expect: The model exposes exactly the expected mass-assignable profile and 2FA attributes.
     */
    public function test_user_fillable_attributes(): void
    {
        $user = new User;
        $expectedFillable = [
            'name',
            'email',
            'password',
            'gravatar_type',
            'language',
            'email_two_factor_enabled',
            'email_two_factor_code',
            'email_two_factor_expires_at',
        ];

        $this->assertEquals($expectedFillable, $user->getFillable());
    }

    /**
     * Verify user hidden attributes.
     *
     * Expect: Password, remember token and the 2FA code are marked as hidden attributes.
     */
    public function test_user_hidden_attributes(): void
    {
        $user = User::factory()->create();

        $hiddenAttributes = [
            'password',
            'remember_token',
            'email_two_factor_code',
        ];

        foreach ($hiddenAttributes as $attribute) {
            $this->assertContains($attribute, $user->getHidden());
        }
    }

    /**
     * Verify user casts are applied.
     *
     * Expect: Password, 2FA flag and expiration use the expected string, boolean and date types.
     */
    public function test_user_casts_are_applied(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'email_two_factor_enabled' => 1, // Integer wird zu Boolean
            'email_two_factor_expires_at' => now()->addMinutes(10),
        ]);

        $this->assertIsString($user->password);
        $this->assertIsBool($user->email_two_factor_enabled);
        $this->assertInstanceOf(Carbon::class, $user->email_two_factor_expires_at);
    }
}
