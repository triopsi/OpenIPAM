<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'email_two_factor_enabled' => true,
            'email_two_factor_code' => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'email_two_factor_expires_at' => now()->addMinutes(10),
            'language' => 'en',
        ];
    }

    /**
     * Indicate that the model does not have two-factor authentication configured.
     */
    public function withoutTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_two_factor_enabled' => false,
            'email_two_factor_code' => null,
            'email_two_factor_expires_at' => null,
        ]);
    }
}
