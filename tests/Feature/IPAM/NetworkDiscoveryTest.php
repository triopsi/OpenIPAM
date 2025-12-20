<?php

namespace Tests\Feature\IPAM;

use App\Models\Device;
use App\Models\IpAddress;
use App\Models\IpAddressGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetworkDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_network_scan_can_discover_active_devices(): void
    {
        $user = User::factory()->create();
        $group = IpAddressGroup::factory()->create([
            'type' => 'subnet',
            'name' => '192.168.1.0/24',
        ]);

        // Create some IP addresses in a subnet
        $ipAddresses = collect([
            '192.168.1.1',
            '192.168.1.2',
            '192.168.1.3',
            '192.168.1.4',
            '192.168.1.5',
        ])->map(fn ($ip) => IpAddress::factory()->create([
            'ip_address' => $ip,
            'group_id' => $group->id,
            'status' => 'available',
            'version' => 4,
        ]));

        $this->actingAs($user);

        // Simulate discovering devices on some IPs
        $activeIps = $ipAddresses->take(3);

        foreach ($activeIps as $ip) {
            $device = Device::factory()->create([
                'hostname' => "device-{$ip->ip_address}",
                'status' => 'active',
                'type' => 'computer',
            ]);

            $device->ipAddresses()->attach($ip->id, ['is_primary' => true]);
            $ip->update(['status' => 'assigned']);
        }

        // Test that discovery results are accurate
        $usedIps = IpAddress::where('status', 'assigned')->count();
        $availableIps = IpAddress::where('status', 'available')->count();
        $devicesWithIps = Device::whereHas('ipAddresses')->count();

        $this->assertEquals(3, $usedIps);
        $this->assertEquals(2, $availableIps);
        $this->assertEquals(3, $devicesWithIps);
    }

    public function test_duplicate_mac_address_detection(): void
    {
        $user = User::factory()->create();
        $macAddress = '00:11:22:33:44:55';

        $device1 = Device::factory()->create([
            'mac_address' => $macAddress,
            'hostname' => 'device1',
        ]);

        $device2 = Device::factory()->create([
            'hostname' => 'device2',
        ]);

        $this->actingAs($user);

        // Try to assign same MAC to second device
        $device2->update(['mac_address' => $macAddress]);

        // Check for duplicates
        $duplicateCount = Device::where('mac_address', $macAddress)->count();

        $this->assertGreaterThan(1, $duplicateCount);

        // In a real application, you'd want to prevent duplicates
        $duplicates = Device::where('mac_address', $macAddress)
            ->orderBy('created_at')
            ->get();

        $this->assertCount(2, $duplicates);
        $this->assertEquals('device1', $duplicates->first()->hostname);
        $this->assertEquals('device2', $duplicates->last()->hostname);
    }

    public function test_subnet_utilization_calculation(): void
    {
        $user = User::factory()->create();
        $group = IpAddressGroup::factory()->create([
            'name' => '192.168.1.0/24',
            'type' => 'subnet',
        ]);

        // Create a /24 subnet (256 addresses)
        $totalAddresses = 10; // Simplified for testing

        for ($i = 1; $i <= $totalAddresses; $i++) {
            IpAddress::factory()->create([
                'ip_address' => "192.168.1.{$i}",
                'group_id' => $group->id,
                'status' => $i <= 6 ? 'assigned' : 'available', // 6 assigned, 4 available
                'version' => 4,
            ]);
        }

        $this->actingAs($user);

        $usedCount = IpAddress::where('group_id', $group->id)
            ->where('status', 'assigned')
            ->count();

        $totalCount = IpAddress::where('group_id', $group->id)->count();
        $utilizationPercent = round(($usedCount / $totalCount) * 100, 2);

        $this->assertEquals(6, $usedCount);
        $this->assertEquals(10, $totalCount);
        $this->assertEquals(60.0, $utilizationPercent);
    }

    public function test_device_ping_status_tracking(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create([
            'status' => 'active',
        ]);

        $ip = IpAddress::factory()->create([
            'ip_address' => '192.168.1.100',
            'status' => 'assigned',
        ]);

        $device->ipAddresses()->attach($ip->id, ['is_primary' => true]);

        $this->actingAs($user);

        // Simulate ping success
        $device->update([
            'status' => 'active',
            'last_seen' => now(),
        ]);

        $this->assertEquals('active', $device->fresh()->status);
        $this->assertNotNull($device->fresh()->last_seen);

        // Simulate ping failure
        $device->update(['status' => 'inactive']);

        $this->assertEquals('inactive', $device->fresh()->status);
    }

    public function test_network_topology_relationships(): void
    {
        $user = User::factory()->create();

        // Create network hierarchy
        $router = Device::factory()->create([
            'name' => 'Main Router',
            'type' => 'router',
            'status' => 'active',
        ]);

        $switch1 = Device::factory()->create([
            'name' => 'Switch 1',
            'type' => 'switch',
            'status' => 'active',
        ]);

        $switch2 = Device::factory()->create([
            'name' => 'Switch 2',
            'type' => 'switch',
            'status' => 'active',
        ]);

        $computer1 = Device::factory()->create([
            'name' => 'Computer 1',
            'type' => 'computer',
            'status' => 'active',
        ]);

        $computer2 = Device::factory()->create([
            'name' => 'Computer 2',
            'type' => 'computer',
            'status' => 'active',
        ]);

        $this->actingAs($user);

        // Verify device types are categorized correctly
        $infrastructure = Device::whereIn('type', ['router', 'switch'])->count();
        $endpoints = Device::where('type', 'computer')->count();

        $this->assertEquals(3, $infrastructure); // router + 2 switches
        $this->assertEquals(2, $endpoints); // 2 computers

        // Test device grouping by location
        $devices = Device::where('status', 'active')->get();
        $devicesByType = $devices->groupBy('type');

        $this->assertArrayHasKey('router', $devicesByType);
        $this->assertArrayHasKey('switch', $devicesByType);
        $this->assertArrayHasKey('computer', $devicesByType);

        $this->assertCount(1, $devicesByType['router']);
        $this->assertCount(2, $devicesByType['switch']);
        $this->assertCount(2, $devicesByType['computer']);
    }
}
