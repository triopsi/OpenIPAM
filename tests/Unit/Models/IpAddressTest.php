<?php

namespace Tests\Unit\Models;

use App\Models\Device;
use App\Models\IpAddress;
use App\Models\IpAddressGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IpAddressTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verify IP address can be created with valid attributes.
     *
     * Expect: The saved address retains its supplied network fields and available status.
     */
    public function test_ip_address_can_be_created_with_valid_attributes(): void
    {
        $ipAddress = IpAddress::create([
            'ip_address' => '192.168.1.10',
            'version' => 4,
            'subnet' => '192.168.1.0/24',
            'gateway' => '192.168.1.1',
            'description' => 'Test IP address',
            'status' => 'available',
        ]);

        $this->assertInstanceOf(IpAddress::class, $ipAddress);
        $this->assertEquals('192.168.1.10', $ipAddress->ip_address);
        $this->assertEquals(4, $ipAddress->version);
        $this->assertEquals('192.168.1.0/24', $ipAddress->subnet);
        $this->assertEquals('192.168.1.1', $ipAddress->gateway);
        $this->assertEquals('available', $ipAddress->status);
    }

    /**
     * Verify IP address version is cast to integer.
     *
     * Expect: The model exposes IP version 4 as an integer.
     */
    public function test_ip_address_version_is_cast_to_integer(): void
    {
        $ipAddress = IpAddress::create([
            'ip_address' => '192.168.1.10',
            'version' => '4',  // String wird zu Integer gecastet
            'status' => 'available',
        ]);

        $this->assertIsInt($ipAddress->version);
        $this->assertEquals(4, $ipAddress->version);
    }

    /**
     * Verify IP address can check if available.
     *
     * Expect: Only an address with available status reports itself as available.
     */
    public function test_ip_address_can_check_if_available(): void
    {
        $availableIp = IpAddress::factory()->create(['status' => 'available']);
        $usedIp = IpAddress::factory()->create(['status' => 'assigned']);
        $reservedIp = IpAddress::factory()->create(['status' => 'reserved']);

        $this->assertTrue($availableIp->isAvailable());
        $this->assertFalse($usedIp->isAvailable());
        $this->assertFalse($reservedIp->isAvailable());
    }

    /**
     * Verify IP address belongs to group.
     *
     * Expect: The address resolves its related group and the group's expected name.
     */
    public function test_ip_address_belongs_to_group(): void
    {
        $group = IpAddressGroup::factory()->create(['name' => 'VLAN 100']);
        $ipAddress = IpAddress::factory()->create(['group_id' => $group->id]);

        $this->assertInstanceOf(IpAddressGroup::class, $ipAddress->group);
        $this->assertEquals('VLAN 100', $ipAddress->group->name);
    }

    /**
     * Verify IP address can have multiple devices.
     *
     * Expect: The relationship returns both devices attached to the address.
     */
    public function test_ip_address_can_have_multiple_devices(): void
    {
        $ipAddress = IpAddress::factory()->create();
        $device1 = Device::factory()->create(['name' => 'Server 1']);
        $device2 = Device::factory()->create(['name' => 'Server 2']);

        $ipAddress->devices()->attach($device1->id, ['is_primary' => true]);
        $ipAddress->devices()->attach($device2->id, ['is_primary' => false]);

        $this->assertCount(2, $ipAddress->devices);
        $this->assertTrue($ipAddress->devices->contains($device1));
        $this->assertTrue($ipAddress->devices->contains($device2));
    }

    /**
     * Verify IPv4 address validation.
     *
     * Expect: All valid IPv4 fixtures are stored with version 4.
     */
    public function test_ipv4_address_validation(): void
    {
        $validIpv4Addresses = [
            '192.168.1.1',
            '10.0.0.1',
            '172.16.0.1',
            '8.8.8.8',
            '0.0.0.0',
            '255.255.255.255',
        ];

        foreach ($validIpv4Addresses as $ip) {
            $ipAddress = IpAddress::create([
                'ip_address' => $ip,
                'version' => 4,
                'status' => 'available',
            ]);

            $this->assertEquals($ip, $ipAddress->ip_address);
            $this->assertEquals(4, $ipAddress->version);
        }
    }

    /**
     * Verify IPv6 address support.
     *
     * Expect: All IPv6 fixtures are stored unchanged with version 6.
     */
    public function test_ipv6_address_support(): void
    {
        $validIpv6Addresses = [
            '2001:db8::1',
            '::1',
            '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
            'fe80::1',
        ];

        foreach ($validIpv6Addresses as $ip) {
            $ipAddress = IpAddress::create([
                'ip_address' => $ip,
                'version' => 6,
                'status' => 'available',
            ]);

            $this->assertEquals($ip, $ipAddress->ip_address);
            $this->assertEquals(6, $ipAddress->version);
        }
    }

    /**
     * Verify IP address status values.
     *
     * Expect: Each supported status is persisted as supplied.
     */
    public function test_ip_address_status_values(): void
    {
        $statuses = ['available', 'assigned', 'reserved', 'blocked'];

        foreach ($statuses as $status) {
            $ipAddress = IpAddress::factory()->create(['status' => $status]);
            $this->assertEquals($status, $ipAddress->status);
        }
    }

    /**
     * Verify IP address subnet formats.
     *
     * Expect: Each subnet-format fixture is persisted unchanged.
     */
    public function test_ip_address_subnet_formats(): void
    {
        $subnetFormats = [
            '192.168.1.0/24',
            '10.0.0.0/8',
            '172.16.0.0/16',
            '2001:db8::/32',
        ];

        foreach ($subnetFormats as $subnet) {
            $ipAddress = IpAddress::factory()->create(['subnet' => $subnet]);
            $this->assertEquals($subnet, $ipAddress->subnet);
        }
    }

    /**
     * Verify IP address relationship pivot data.
     *
     * Expect: The device relationship exposes the primary flag and pivot timestamps.
     */
    public function test_ip_address_relationship_pivot_data(): void
    {
        $ipAddress = IpAddress::factory()->create();
        $device = Device::factory()->create();

        $ipAddress->devices()->attach($device->id, [
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $attachedDevice = $ipAddress->devices()->first();

        $this->assertEquals(1, $attachedDevice->pivot->is_primary); // SQLite/MySQL speichert boolean als 1/0
        $this->assertNotNull($attachedDevice->pivot->created_at);
        $this->assertNotNull($attachedDevice->pivot->updated_at);
    }
}
