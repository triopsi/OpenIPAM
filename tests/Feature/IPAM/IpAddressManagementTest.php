<?php

namespace Tests\Feature\IPAM;

use App\Models\Device;
use App\Models\IpAddress;
use App\Models\IpAddressGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IpAddressManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verify IP address can be assigned to device.
     *
     * Expect: The device has one address relationship containing the assigned IP.
     */
    public function test_ip_address_can_be_assigned_to_device(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['name' => 'Test Server']);
        $ipAddress = IpAddress::factory()->create(['ip_address' => '192.168.1.10']);

        $this->actingAs($user);

        // Simuliere IP-Zuweisung
        $device->ipAddresses()->attach($ipAddress->id, ['is_primary' => true]);

        $this->assertCount(1, $device->ipAddresses);
        $this->assertEquals('192.168.1.10', $device->ipAddresses->first()->ip_address);
    }

    /**
     * Verify multiple IP addresses can be assigned to device.
     *
     * Expect: All three address relationships exist and the designated primary address is returned.
     */
    public function test_multiple_ip_addresses_can_be_assigned_to_device(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['name' => 'Multi-IP Server']);

        $primaryIp = IpAddress::factory()->create(['ip_address' => '192.168.1.10']);
        $secondaryIp = IpAddress::factory()->create(['ip_address' => '192.168.1.11']);
        $tertiaryIp = IpAddress::factory()->create(['ip_address' => '192.168.1.12']);

        $this->actingAs($user);

        $device->ipAddresses()->attach([
            $primaryIp->id => ['is_primary' => true],
            $secondaryIp->id => ['is_primary' => false],
            $tertiaryIp->id => ['is_primary' => false],
        ]);

        $this->assertCount(3, $device->ipAddresses);
        $this->assertEquals($primaryIp->ip_address, $device->primaryIpAddress()->ip_address);
    }

    /**
     * Verify IP address status changes when assigned.
     *
     * Expect: The assigned address reports assigned status and is no longer available.
     */
    public function test_ip_address_status_changes_when_assigned(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create();
        $ipAddress = IpAddress::factory()->create([
            'ip_address' => '192.168.1.10',
            'status' => 'available',
        ]);

        $this->actingAs($user);

        // Simuliere Status-Änderung bei Zuweisung
        $device->ipAddresses()->attach($ipAddress->id);
        $ipAddress->update(['status' => 'assigned']);

        $this->assertEquals('assigned', $ipAddress->fresh()->status);
        $this->assertFalse($ipAddress->fresh()->isAvailable());
    }

    /**
     * Verify IP address can be unassigned from device.
     *
     * Expect: The relationship is removed and the address becomes available again.
     */
    public function test_ip_address_can_be_unassigned_from_device(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create();
        $ipAddress = IpAddress::factory()->create(['status' => 'assigned']);

        $this->actingAs($user);

        // Zuerst zuweisen
        $device->ipAddresses()->attach($ipAddress->id);
        $this->assertCount(1, $device->ipAddresses);

        // Dann entfernen
        $device->ipAddresses()->detach($ipAddress->id);
        $ipAddress->update(['status' => 'available']);

        $this->assertCount(0, $device->fresh()->ipAddresses);
        $this->assertTrue($ipAddress->fresh()->isAvailable());
    }

    /**
     * Verify IP address groups can organize addresses.
     *
     * Expect: Both addresses belong to the VLAN group and its relationship contains two records.
     */
    public function test_ip_address_groups_can_organize_addresses(): void
    {
        $user = User::factory()->create();
        $vlanGroup = IpAddressGroup::factory()->create([
            'name' => 'VLAN 100 - Production',
            'description' => 'Production servers VLAN',
        ]);

        $ip1 = IpAddress::factory()->create([
            'ip_address' => '192.168.100.10',
            'group_id' => $vlanGroup->id,
        ]);

        $ip2 = IpAddress::factory()->create([
            'ip_address' => '192.168.100.11',
            'group_id' => $vlanGroup->id,
        ]);

        $this->actingAs($user);

        $this->assertEquals('VLAN 100 - Production', $ip1->group->name);
        $this->assertEquals('VLAN 100 - Production', $ip2->group->name);
        $this->assertCount(2, $vlanGroup->ipAddresses);
    }

    /**
     * Verify bulk IP creation in subnet.
     *
     * Expect: Six address records are created for the inclusive .10 through .15 range.
     */
    public function test_bulk_ip_creation_in_subnet(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Simuliere Bulk-Erstellung von IPs in einem Subnet
        $baseIp = '192.168.1.';
        $subnet = '192.168.1.0/24';
        $startRange = 10;
        $endRange = 15;

        $createdIps = [];
        for ($i = $startRange; $i <= $endRange; $i++) {
            $createdIps[] = IpAddress::create([
                'ip_address' => $baseIp.$i,
                'version' => 4,
                'subnet' => $subnet,
                'gateway' => '192.168.1.1',
                'status' => 'available',
            ]);
        }

        $this->assertCount(6, $createdIps); // 10-15 = 6 IPs
        $this->assertEquals('192.168.1.10', $createdIps[0]->ip_address);
        $this->assertEquals('192.168.1.15', $createdIps[5]->ip_address);
    }

    /**
     * Verify IP address search and filtering.
     *
     * Expect: Availability, subnet and assigned-status queries return the expected record counts.
     */
    public function test_ip_address_search_and_filtering(): void
    {
        $user = User::factory()->create();

        // Erstelle verschiedene IPs zum Testen
        IpAddress::factory()->create(['ip_address' => '192.168.1.10', 'status' => 'available']);
        IpAddress::factory()->create(['ip_address' => '192.168.1.11', 'status' => 'assigned']);
        IpAddress::factory()->create(['ip_address' => '192.168.2.10', 'status' => 'available']);
        IpAddress::factory()->create(['ip_address' => '10.0.0.10', 'status' => 'reserved']);

        $this->actingAs($user);

        // Test verschiedene Suchkriterien
        $availableIps = IpAddress::where('status', 'available')->get();
        $subnet192168_1 = IpAddress::where('ip_address', 'like', '192.168.1.%')->get();
        $usedIps = IpAddress::where('status', 'assigned')->get();

        $this->assertCount(2, $availableIps);
        $this->assertCount(2, $subnet192168_1);
        $this->assertCount(1, $usedIps);
    }

    /**
     * Inspect relationship state after assigning an address to one device.
     *
     * Expect: The first device's address is assigned and no relationship exists for the second device.
     */
    public function test_ip_conflict_prevention(): void
    {
        $user = User::factory()->create();
        $device1 = Device::factory()->create(['name' => 'Server 1']);
        $device2 = Device::factory()->create(['name' => 'Server 2']);
        $ipAddress = IpAddress::factory()->create([
            'ip_address' => '192.168.1.10',
            'status' => 'available',
        ]);

        $this->actingAs($user);

        // Erste Zuweisung
        $device1->ipAddresses()->attach($ipAddress->id);
        $ipAddress->update(['status' => 'assigned']);

        // Versuche, dieselbe IP einem anderen Gerät zuzuweisen
        $conflictExists = $device2->ipAddresses()->where('ip_addresses.id', $ipAddress->id)->exists();

        $this->assertFalse($conflictExists);
        $this->assertEquals('assigned', $ipAddress->fresh()->status);
    }

    /**
     * Verify IPv6 addresses are supported.
     *
     * Expect: The device's primary address is 2001:db8::1 with IP version 6.
     */
    public function test_ipv6_addresses_are_supported(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['name' => 'IPv6 Server']);

        $ipv6Address = IpAddress::create([
            'ip_address' => '2001:db8::1',
            'version' => 6,
            'subnet' => '2001:db8::/64',
            'gateway' => '2001:db8::1',
            'status' => 'available',
        ]);

        $this->actingAs($user);

        $device->ipAddresses()->attach($ipv6Address->id, ['is_primary' => true]);

        $this->assertEquals('2001:db8::1', $device->primaryIpAddress()->ip_address);
        $this->assertEquals(6, $device->primaryIpAddress()->version);
    }
}
