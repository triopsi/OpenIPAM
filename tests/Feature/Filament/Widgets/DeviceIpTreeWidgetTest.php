<?php

namespace Tests\Feature\Filament\Widgets;

use App\Filament\Widgets\DeviceIpTreeWidget;
use App\Models\Device;
use App\Models\IpAddress;
use App\Models\IpAddressGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceIpTreeWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_widget_displays_correct_statistics(): void
    {
        // Erstelle Test-Daten
        $group = IpAddressGroup::factory()->create(['name' => 'Test Group']);

        $device1 = Device::factory()->create(['name' => 'Device 1']);
        $device2 = Device::factory()->create(['name' => 'Device 2']);
        $device3 = Device::factory()->create(['name' => 'Device 3']); // ohne IP

        $ip1 = IpAddress::factory()->create(['status' => 'available', 'group_id' => $group->id]);
        $ip2 = IpAddress::factory()->create(['status' => 'available', 'group_id' => null]);
        $ip3 = IpAddress::factory()->create(['status' => 'available']);

        // Weise IPs zu
        $device1->ipAddresses()->attach($ip1->id, ['is_primary' => true]);
        $ip1->update(['status' => 'assigned']);

        $device2->ipAddresses()->attach($ip2->id, ['is_primary' => true]);
        $ip2->update(['status' => 'assigned']);

        $widget = new DeviceIpTreeWidget;
        $data = $widget->getViewData();

        $this->assertEquals(3, $data['statistics']['totalDevices']);
        $this->assertEquals(2, $data['statistics']['devicesWithIps']);
        $this->assertEquals(1, $data['statistics']['devicesWithoutIps']);
        $this->assertEquals(2, $data['statistics']['usedIpAddresses']);
        $this->assertEquals(1, $data['statistics']['availableIpAddresses']);
    }

    public function test_widget_groups_devices_correctly(): void
    {
        // Erstelle IP-Gruppe
        $serverGroup = IpAddressGroup::factory()->create(['name' => 'Servers']);

        // Geräte erstellen
        $serverDevice = Device::factory()->create(['name' => 'Server 1', 'type' => 'server']);
        $workstationDevice = Device::factory()->create(['name' => 'Workstation 1', 'type' => 'workstation']);
        $deviceWithoutIp = Device::factory()->create(['name' => 'No IP Device']);

        // IPs erstellen
        $serverIp = IpAddress::factory()->create(['status' => 'available', 'group_id' => $serverGroup->id]);
        $ungroupedIp = IpAddress::factory()->create(['status' => 'available', 'group_id' => null]);

        // IPs zuweisen
        $serverDevice->ipAddresses()->attach($serverIp->id, ['is_primary' => true]);
        $serverIp->update(['status' => 'assigned']);

        $workstationDevice->ipAddresses()->attach($ungroupedIp->id, ['is_primary' => true]);
        $ungroupedIp->update(['status' => 'assigned']);

        $widget = new DeviceIpTreeWidget;
        $data = $widget->getViewData();

        // Prüfe Gruppierung
        $this->assertArrayHasKey('Servers', $data['groupedData']);
        $this->assertArrayHasKey('Without IP group', $data['groupedData']);
        $this->assertArrayHasKey('Devices without IP addresses', $data['groupedData']);

        // Prüfe Device-Zuordnung
        $serverGroupData = $data['groupedData']['Servers'];
        $this->assertEquals('ip-group', $serverGroupData['type']);
        $this->assertEquals(1, $serverGroupData['count']);
        $this->assertTrue($serverGroupData['devices']->contains('name', 'Server 1'));

        $ungroupedData = $data['groupedData']['Without IP group'];
        $this->assertEquals('ungrouped', $ungroupedData['type']);
        $this->assertEquals(1, $ungroupedData['count']);
        $this->assertTrue($ungroupedData['devices']->contains('name', 'Workstation 1'));

        $noIpData = $data['groupedData']['Devices without IP addresses'];
        $this->assertEquals('no-ip', $noIpData['type']);
        $this->assertEquals(1, $noIpData['count']);
        $this->assertTrue($noIpData['devices']->contains('name', 'No IP Device'));
    }

    public function test_widget_handles_empty_data(): void
    {
        $widget = new DeviceIpTreeWidget;
        $data = $widget->getViewData();

        $this->assertEquals(0, $data['statistics']['totalDevices']);
        $this->assertEquals(0, $data['statistics']['devicesWithIps']);
        $this->assertEquals(0, $data['statistics']['devicesWithoutIps']);
        $this->assertEmpty($data['groupedData']);
    }

    public function test_widget_handles_devices_with_multiple_ips(): void
    {
        $device = Device::factory()->create(['name' => 'Multi IP Device']);

        $ip1 = IpAddress::factory()->create(['status' => 'available']);
        $ip2 = IpAddress::factory()->create(['status' => 'available']);

        // Weise mehrere IPs zu
        $device->ipAddresses()->attach($ip1->id, ['is_primary' => true]);
        $device->ipAddresses()->attach($ip2->id, ['is_primary' => false]);

        $ip1->update(['status' => 'assigned']);
        $ip2->update(['status' => 'assigned']);

        $widget = new DeviceIpTreeWidget;
        $data = $widget->getViewData();

        $this->assertEquals(1, $data['statistics']['totalDevices']);
        $this->assertEquals(1, $data['statistics']['devicesWithIps']);
        $this->assertEquals(2, $data['statistics']['usedIpAddresses']);

        // Device sollte nur einmal in der Gruppierung erscheinen
        $ungroupedData = $data['groupedData']['Without IP group'];
        $this->assertEquals(1, $ungroupedData['count']);

        // Device sollte beide IPs anzeigen
        $device->refresh();
        $this->assertEquals(2, $device->ipAddresses->count());
    }

    public function test_widget_is_accessible_by_admin(): void
    {
        $this->assertTrue(DeviceIpTreeWidget::canView());
    }
}
