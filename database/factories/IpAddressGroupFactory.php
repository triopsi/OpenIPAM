<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IpAddressGroup>
 */
class IpAddressGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'VLAN 100 - Production',
                'VLAN 200 - Development',
                'VLAN 300 - Testing',
                'DMZ Network',
                'Guest Network',
                'Management Network',
                'Server Network',
                'Office Building A',
                'Office Building B',
                'Datacenter Rack 1-5',
                'Printer Network',
            ]),
            'description' => $this->faker->optional()->sentence(),
            'color' => $this->faker->hexColor(),
            'type' => $this->faker->randomElement(['vlan', 'room', 'building', 'general', 'subnet']),
        ];
    }

    /**
     * Indicate that the group is a VLAN.
     */
    public function vlan(?int $vlanId = null): static
    {
        $vlanId = $vlanId ?: $this->faker->numberBetween(1, 4094);

        return $this->state(fn (array $attributes) => [
            'name' => "VLAN {$vlanId}",
            'type' => 'vlan',
            'description' => "VLAN {$vlanId} network segment",
        ]);
    }

    /**
     * Indicate that the group is for a specific room.
     */
    public function room(?string $roomName = null): static
    {
        $roomName = $roomName ?: 'Room '.$this->faker->numberBetween(100, 999);

        return $this->state(fn (array $attributes) => [
            'name' => $roomName,
            'type' => 'room',
            'description' => "IP addresses for {$roomName}",
        ]);
    }

    /**
     * Indicate that the group is for a building.
     */
    public function building(?string $buildingName = null): static
    {
        $buildingName = $buildingName ?: 'Building '.$this->faker->randomLetter();

        return $this->state(fn (array $attributes) => [
            'name' => $buildingName,
            'type' => 'building',
            'description' => "IP addresses for {$buildingName}",
        ]);
    }
}
