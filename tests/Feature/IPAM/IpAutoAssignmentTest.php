<?php

namespace Tests\Feature\IPAM;

use App\Models\Device;
use App\Models\IpAddress;
use App\Models\IpAddressGroup;
use App\Models\Setting;
use App\Services\IpAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IpAutoAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Standard-Einstellungen laden
        Setting::create([
            'key' => 'auto_assign_primary_ip',
            'value' => '1',
            'type' => 'boolean',
            'group' => 'ipam',
            'label' => 'Auto-Assign Primary IP',
            'description' => 'Automatically assign primary IP',
        ]);

        Setting::create([
            'key' => 'default_ip_group',
            'value' => null,
            'type' => 'integer',
            'group' => 'ipam',
            'label' => 'Default IP Group',
            'description' => 'Default group for IP assignment',
        ]);
    }

    /**
     * Verify service gets next available IP without group.
     *
     * Expect: The service returns the next available unassigned address from the fixtures.
     */
    public function test_service_gets_next_available_ip_without_group(): void
    {
        // Erstelle verfügbare IP-Adressen
        $ip1 = IpAddress::factory()->create(['ip_address' => '192.168.1.10', 'status' => 'assigned']);
        $ip2 = IpAddress::factory()->create(['ip_address' => '192.168.1.11', 'status' => 'available']);
        $ip3 = IpAddress::factory()->create(['ip_address' => '192.168.1.12', 'status' => 'available']);

        $service = new IpAssignmentService;
        $nextIp = $service->getNextAvailableIp();

        $this->assertNotNull($nextIp);
        $this->assertEquals('192.168.1.11', $nextIp->ip_address);
    }

    /**
     * Verify service gets next available IP from preferred group.
     *
     * Expect: The selected address belongs to the explicitly preferred group.
     */
    public function test_service_gets_next_available_ip_from_preferred_group(): void
    {
        // Erstelle Gruppen
        $group1 = IpAddressGroup::factory()->create(['name' => 'Group 1']);
        $group2 = IpAddressGroup::factory()->create(['name' => 'Group 2']);

        // Erstelle IPs in verschiedenen Gruppen
        IpAddress::factory()->create([
            'ip_address' => '192.168.1.10',
            'status' => 'available',
            'group_id' => $group1->id,
        ]);

        $preferredIp = IpAddress::factory()->create([
            'ip_address' => '192.168.2.10',
            'status' => 'available',
            'group_id' => $group2->id,
        ]);

        $service = new IpAssignmentService;
        $nextIp = $service->getNextAvailableIp($group2->id);

        $this->assertNotNull($nextIp);
        $this->assertEquals('192.168.2.10', $nextIp->ip_address);
        $this->assertEquals($group2->id, $nextIp->group_id);
    }

    /**
     * Verify service assigns IP to device.
     *
     * Expect: The assignment relationship is persisted and the address status becomes assigned.
     */
    public function test_service_assigns_ip_to_device(): void
    {
        $device = Device::factory()->create();
        $ipAddress = IpAddress::factory()->create(['status' => 'available']);

        $service = new IpAssignmentService;
        $service->assignIpToDevice($device->id, $ipAddress->id, true);

        // Prüfe Datenbankverbindung
        $this->assertDatabaseHas('device_ip_address', [
            'device_id' => $device->id,
            'ip_address_id' => $ipAddress->id,
            'is_primary' => true,
        ]);

        // Prüfe IP-Status
        $ipAddress->refresh();
        $this->assertEquals('assigned', $ipAddress->status);
    }

    /**
     * Verify service unassigns IP from device.
     *
     * Expect: The relationship is removed and the released address status becomes available.
     */
    public function test_service_unassigns_ip_from_device(): void
    {
        $device = Device::factory()->create();
        $ipAddress = IpAddress::factory()->create(['status' => 'assigned']);

        // Erstelle Verbindung
        DB::table('device_ip_address')->insert([
            'device_id' => $device->id,
            'ip_address_id' => $ipAddress->id,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = new IpAssignmentService;
        $service->unassignIpFromDevice($device->id, $ipAddress->id);

        // Prüfe dass Verbindung entfernt wurde
        $this->assertDatabaseMissing('device_ip_address', [
            'device_id' => $device->id,
            'ip_address_id' => $ipAddress->id,
        ]);

        // Prüfe IP-Status
        $ipAddress->refresh();
        $this->assertEquals('available', $ipAddress->status);
    }

    /**
     * Verify service respects default group setting.
     *
     * Expect: The returned address belongs to the configured default group.
     */
    public function test_service_respects_default_group_setting(): void
    {
        // Setze Standard-Gruppe
        $defaultGroup = IpAddressGroup::factory()->create(['name' => 'Default Group']);
        Setting::where('key', 'default_ip_group')->update(['value' => $defaultGroup->id]);

        // Erstelle IPs
        $otherIp = IpAddress::factory()->create([
            'ip_address' => '192.168.1.10',
            'status' => 'available',
            'group_id' => null,
        ]);

        $defaultIp = IpAddress::factory()->create([
            'ip_address' => '192.168.2.10',
            'status' => 'available',
            'group_id' => $defaultGroup->id,
        ]);

        $service = new IpAssignmentService;
        $nextIp = $service->getNextAvailableIp();

        // Sollte IP aus Standard-Gruppe bevorzugen
        $this->assertEquals($defaultIp->id, $nextIp->id);
        $this->assertEquals($defaultGroup->id, $nextIp->group_id);
    }

    /**
     * Verify service returns null when auto assign disabled.
     *
     * Expect: Disabling automatic primary-address assignment makes the service return null.
     */
    public function test_service_returns_null_when_auto_assign_disabled(): void
    {
        // Deaktiviere Auto-Assignment
        Setting::where('key', 'auto_assign_primary_ip')->update(['value' => '0']);

        IpAddress::factory()->create(['status' => 'available']);

        $service = new IpAssignmentService;
        $nextIp = $service->getNextAvailableIp();

        $this->assertNull($nextIp);
    }

    /**
     * Verify service returns null when no available ips.
     *
     * Expect: The service returns null when there are no available addresses.
     */
    public function test_service_returns_null_when_no_available_ips(): void
    {
        // Nur verwendete IPs
        IpAddress::factory()->create(['status' => 'assigned']);
        IpAddress::factory()->create(['status' => 'reserved']);

        $service = new IpAssignmentService;
        $nextIp = $service->getNextAvailableIp();

        $this->assertNull($nextIp);
    }

    /**
     * Verify service prepares IP options grouped.
     *
     * Expect: Address options are grouped under their named groups or the ungrouped label.
     */
    public function test_service_prepares_ip_options_grouped(): void
    {
        $group1 = IpAddressGroup::factory()->create(['name' => 'Servers']);
        $group2 = IpAddressGroup::factory()->create(['name' => 'Workstations']);

        IpAddress::factory()->create([
            'ip_address' => '192.168.1.10',
            'status' => 'available',
            'group_id' => $group1->id,
        ]);

        IpAddress::factory()->create([
            'ip_address' => '192.168.2.10',
            'status' => 'available',
            'group_id' => $group2->id,
        ]);

        IpAddress::factory()->create([
            'ip_address' => '192.168.3.10',
            'status' => 'available',
            'group_id' => null,
        ]);

        $service = new IpAssignmentService;
        $options = $service->prepareIpOptionsForDevice();

        $this->assertArrayHasKey('Servers', $options);
        $this->assertArrayHasKey('Workstations', $options);
        $this->assertArrayHasKey('Ohne Gruppe', $options);

        $this->assertStringContainsString('192.168.1.10', reset($options['Servers']));
        $this->assertStringContainsString('192.168.2.10', reset($options['Workstations']));
        $this->assertStringContainsString('192.168.3.10', reset($options['Ohne Gruppe']));
    }
}
