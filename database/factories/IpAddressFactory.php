<?php

namespace Database\Factories;

use App\Models\IpAddressGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IpAddress>
 */
class IpAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $version = $this->faker->randomElement([4, 6]);

        return [
            'ip_address' => $version === 4 ? $this->faker->ipv4() : $this->faker->ipv6(),
            'version' => $version,
            'subnet' => $version === 4
                ? $this->faker->ipv4().'/'.$this->faker->randomElement([24, 25, 26, 27, 28])
                : $this->faker->ipv6().'/'.$this->faker->randomElement([64, 48, 32]),
            'gateway' => $version === 4 ? $this->faker->ipv4() : $this->faker->ipv6(),
            'description' => $this->faker->optional()->sentence(),
            'status' => $this->faker->randomElement(['available', 'assigned', 'reserved', 'blocked']),
            'group_id' => null, // Will be set manually when needed
        ];
    }

    /**
     * Indicate that the IP address is IPv4.
     */
    public function ipv4(): static
    {
        return $this->state(fn (array $attributes) => [
            'ip_address' => $this->faker->ipv4(),
            'version' => 4,
            'subnet' => $this->faker->ipv4().'/'.$this->faker->randomElement([24, 25, 26, 27, 28]),
            'gateway' => $this->faker->ipv4(),
        ]);
    }

    /**
     * Indicate that the IP address is IPv6.
     */
    public function ipv6(): static
    {
        return $this->state(fn (array $attributes) => [
            'ip_address' => $this->faker->ipv6(),
            'version' => 6,
            'subnet' => $this->faker->ipv6().'/'.$this->faker->randomElement([64, 48, 32]),
            'gateway' => $this->faker->ipv6(),
        ]);
    }

    /**
     * Indicate that the IP address is available.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'available',
        ]);
    }

    /**
     * Indicate that the IP address is used.
     */
    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'assigned',
        ]);
    }

    /**
     * Indicate that the IP address is in a specific subnet.
     */
    public function inSubnet(string $subnet): static
    {
        // Generate IP in specific subnet (simplified for demo)
        $parts = explode('.', explode('/', $subnet)[0]);
        $ip = $parts[0].'.'.$parts[1].'.'.$parts[2].'.'.$this->faker->numberBetween(1, 254);

        return $this->state(fn (array $attributes) => [
            'ip_address' => $ip,
            'subnet' => $subnet,
            'version' => 4,
        ]);
    }

    /**
     * Indicate that the IP address belongs to a group.
     */
    public function inGroup(IpAddressGroup $group): static
    {
        return $this->state(fn (array $attributes) => [
            'group_id' => $group->id,
        ]);
    }
}
