<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company().' '.$this->faker->randomElement(['Server', 'Workstation', 'Laptop']),
            'hostname' => $this->faker->domainWord().'.'.$this->faker->domainName(),
            'mac_address' => strtoupper(implode(':', str_split($this->faker->regexify('[0-9A-F]{12}'), 2))),
            'description' => $this->faker->optional()->sentence(),
            'type' => $this->faker->randomElement(['server', 'workstation', 'printer', 'router', 'switch', 'firewall', 'access_point']),
            'location' => $this->faker->randomElement([
                'Datacenter A - Rack 15',
                'Office Building - Floor 2',
                'Remote Site - Building B',
                'Network Closet - Room 101',
            ]),
            'status' => $this->faker->randomElement(['active', 'inactive', 'maintenance', 'decommissioned']),
        ];
    }

    /**
     * Indicate that the device is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the device is a server.
     */
    public function server(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'server',
            'name' => $this->faker->company().' Server',
        ]);
    }

    /**
     * Indicate that the device is a workstation.
     */
    public function workstation(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'workstation',
            'name' => $this->faker->firstName().'\'s Workstation',
        ]);
    }
}
