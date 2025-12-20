<?php

namespace Tests\Unit\Models;

use App\Models\Device;
use App\Models\IpAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_can_be_created_with_valid_attributes(): void
    {
        $device = Device::create([
            'name' => 'Test Device',
            'hostname' => 'test-device.local',
            'mac_address' => '00:11:22:33:44:55',
            'description' => 'Test description',
            'type' => 'server',
            'location' => 'Datacenter A',
            'status' => 'active',
        ]);

        $this->assertInstanceOf(Device::class, $device);
        $this->assertEquals('Test Device', $device->name);
        $this->assertEquals('test-device.local', $device->hostname);
        $this->assertEquals('00:11:22:33:44:55', $device->mac_address);
        $this->assertEquals('server', $device->type);
        $this->assertEquals('active', $device->status);
    }

    public function test_device_has_fillable_attributes(): void
    {
        $device = new Device;
        $fillable = [
            'name',
            'hostname',
            'mac_address',
            'description',
            'url',
            'type',
            'location',
            'status',
            'last_seen',
        ];
        $this->assertEquals($fillable, $device->getFillable());
    }

    public function test_device_can_have_multiple_ip_addresses(): void
    {
        $device = Device::factory()->create();
        $ip1 = IpAddress::factory()->create(['ip_address' => '192.168.1.10']);
        $ip2 = IpAddress::factory()->create(['ip_address' => '192.168.1.11']);

        $device->ipAddresses()->attach($ip1->id, ['is_primary' => true]);
        $device->ipAddresses()->attach($ip2->id, ['is_primary' => false]);

        $this->assertCount(2, $device->ipAddresses);
        $this->assertTrue($device->ipAddresses->contains($ip1));
        $this->assertTrue($device->ipAddresses->contains($ip2));
    }

    public function test_device_can_get_primary_ip_address(): void
    {
        $device = Device::factory()->create();
        $primaryIp = IpAddress::factory()->create(['ip_address' => '192.168.1.10']);
        $secondaryIp = IpAddress::factory()->create(['ip_address' => '192.168.1.11']);

        $device->ipAddresses()->attach($primaryIp->id, ['is_primary' => true]);
        $device->ipAddresses()->attach($secondaryIp->id, ['is_primary' => false]);

        $retrievedPrimaryIp = $device->primaryIpAddress();

        $this->assertNotNull($retrievedPrimaryIp);
        $this->assertEquals($primaryIp->ip_address, $retrievedPrimaryIp->ip_address);
        $this->assertEquals('192.168.1.10', $retrievedPrimaryIp->ip_address);
    }

    public function test_device_can_have_no_primary_ip_address(): void
    {
        $device = Device::factory()->create();
        $ip = IpAddress::factory()->create();

        $device->ipAddresses()->attach($ip->id, ['is_primary' => false]);

        $primaryIp = $device->primaryIpAddress();

        $this->assertNull($primaryIp);
    }

    public function test_device_relationship_with_ip_addresses_includes_pivot_data(): void
    {
        $device = Device::factory()->create();
        $ip = IpAddress::factory()->create();

        $device->ipAddresses()->attach($ip->id, [
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $attachedIp = $device->ipAddresses()->first();

        $this->assertEquals(1, $attachedIp->pivot->is_primary); // SQLite/MySQL speichert boolean als 1/0
        $this->assertNotNull($attachedIp->pivot->created_at);
        $this->assertNotNull($attachedIp->pivot->updated_at);
    }

    public function test_device_mac_address_format_validation(): void
    {
        // Diese Test würde normalerweise eine Validierung prüfen,
        // die in einem Request oder Model definiert ist
        $validMacAddresses = [
            '00:11:22:33:44:55',
            'AA:BB:CC:DD:EE:FF',
            '12:34:56:78:90:AB',
        ];

        foreach ($validMacAddresses as $macAddress) {
            $device = Device::create([
                'name' => 'Test Device',
                'hostname' => 'test.local',
                'mac_address' => $macAddress,
                'type' => 'server',
                'status' => 'active',
            ]);

            $this->assertEquals($macAddress, $device->mac_address);
        }
    }
}
