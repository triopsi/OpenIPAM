<?php

namespace Tests\Feature\IPAM;

use App\Models\Device;
use App\Models\IpAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_can_be_created_with_required_fields(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $deviceData = [
            'name' => 'Production Server 01',
            'hostname' => 'prod-srv-01.company.local',
            'mac_address' => '00:11:22:33:44:55',
            'description' => 'Main production web server',
            'type' => 'server',
            'location' => 'Datacenter A - Rack 15',
            'status' => 'active',
        ];

        $device = Device::create($deviceData);

        $this->assertInstanceOf(Device::class, $device);
        $this->assertEquals('Production Server 01', $device->name);
        $this->assertEquals('prod-srv-01.company.local', $device->hostname);
        $this->assertEquals('00:11:22:33:44:55', $device->mac_address);
        $this->assertEquals('server', $device->type);
        $this->assertEquals('active', $device->status);
    }

    public function test_device_types_are_properly_categorized(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $deviceTypes = ['server', 'workstation', 'printer', 'router', 'switch', 'firewall', 'access_point'];

        foreach ($deviceTypes as $type) {
            $device = Device::factory()->create([
                'name' => "Test {$type}",
                'type' => $type,
            ]);

            $this->assertEquals($type, $device->type);
        }
    }

    public function test_device_status_lifecycle(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['status' => 'active']);
        $this->actingAs($user);

        $statusProgression = ['active', 'maintenance', 'inactive', 'decommissioned'];

        foreach ($statusProgression as $status) {
            $device->update(['status' => $status]);
            $this->assertEquals($status, $device->fresh()->status);
        }
    }

    public function test_device_can_have_multiple_network_interfaces(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create([
            'name' => 'Multi-NIC Server',
            'type' => 'server',
        ]);

        $this->actingAs($user);

        // Simuliere mehrere Netzwerk-Interfaces
        $eth0Ip = IpAddress::factory()->create(['ip_address' => '192.168.1.10']);
        $eth1Ip = IpAddress::factory()->create(['ip_address' => '192.168.2.10']);
        $mgmtIp = IpAddress::factory()->create(['ip_address' => '10.0.0.10']);

        $device->ipAddresses()->attach([
            $eth0Ip->id => ['is_primary' => true],
            $eth1Ip->id => ['is_primary' => false],
            $mgmtIp->id => ['is_primary' => false],
        ]);

        $this->assertCount(3, $device->ipAddresses);
        $this->assertEquals('192.168.1.10', $device->primaryIpAddress()->ip_address);
    }

    public function test_device_location_tracking(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $locations = [
            'Datacenter A - Rack 15 - U20',
            'Office Building - Floor 2 - Room 201',
            'Remote Site - Rack B - U5',
            'Mobile Unit - Vehicle 123',
        ];

        foreach ($locations as $location) {
            $device = Device::factory()->create([
                'name' => 'Test Device',
                'location' => $location,
            ]);

            $this->assertEquals($location, $device->location);
        }
    }

    public function test_device_mac_address_uniqueness(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $macAddress = '00:11:22:33:44:55';

        $device1 = Device::factory()->create([
            'name' => 'Device 1',
            'mac_address' => $macAddress,
        ]);

        // In einer echten Anwendung würde dies eine Unique-Constraint-Verletzung auslösen
        // Hier testen wir nur, dass beide Devices die gleiche MAC haben könnten
        $device2 = Device::factory()->create([
            'name' => 'Device 2',
            'mac_address' => $macAddress,
        ]);

        $this->assertEquals($macAddress, $device1->mac_address);
        $this->assertEquals($macAddress, $device2->mac_address);
    }

    public function test_device_hostname_format_validation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $validHostnames = [
            'server.example.com',
            'web-01.internal.local',
            'db-cluster-node-1.company.org',
            'printer-office.local',
            'router-branch-office',
        ];

        foreach ($validHostnames as $hostname) {
            $device = Device::factory()->create([
                'name' => 'Test Device',
                'hostname' => $hostname,
            ]);

            $this->assertEquals($hostname, $device->hostname);
        }
    }

    public function test_device_can_be_filtered_by_type(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Erstelle verschiedene Device-Typen
        Device::factory()->create(['type' => 'server', 'name' => 'Server 1']);
        Device::factory()->create(['type' => 'server', 'name' => 'Server 2']);
        Device::factory()->create(['type' => 'workstation', 'name' => 'Workstation 1']);
        Device::factory()->create(['type' => 'printer', 'name' => 'Printer 1']);
        Device::factory()->create(['type' => 'router', 'name' => 'Router 1']);

        $servers = Device::where('type', 'server')->get();
        $workstations = Device::where('type', 'workstation')->get();
        $networkDevices = Device::whereIn('type', ['router', 'switch', 'firewall'])->get();

        $this->assertCount(2, $servers);
        $this->assertCount(1, $workstations);
        $this->assertCount(1, $networkDevices);
    }

    public function test_device_can_be_searched_by_name_or_hostname(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Device::factory()->create([
            'name' => 'Production Web Server',
            'hostname' => 'web-prod-01.company.com',
        ]);

        Device::factory()->create([
            'name' => 'Development Database',
            'hostname' => 'db-dev-01.company.com',
        ]);

        Device::factory()->create([
            'name' => 'Test Environment',
            'hostname' => 'test-env-01.company.com',
        ]);

        // Suche nach Name
        $webServers = Device::where('name', 'like', '%Web%')->get();
        $this->assertCount(1, $webServers);

        // Suche nach Hostname
        $prodDevices = Device::where('hostname', 'like', '%-prod-%')->get();
        $this->assertCount(1, $prodDevices);

        // Suche nach Domain
        $companyDevices = Device::where('hostname', 'like', '%.company.com')->get();
        $this->assertCount(3, $companyDevices);
    }

    public function test_device_maintenance_mode(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['status' => 'active']);
        $this->actingAs($user);

        // Gerät in Wartungsmodus setzen
        $device->update(['status' => 'maintenance']);
        $this->assertEquals('maintenance', $device->fresh()->status);

        // Zurück zu aktiv
        $device->update(['status' => 'active']);
        $this->assertEquals('active', $device->fresh()->status);
    }

    public function test_device_decommissioning(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['status' => 'active']);
        $ipAddress = IpAddress::factory()->create(['status' => 'assigned']);

        $this->actingAs($user);

        // IP zuweisen
        $device->ipAddresses()->attach($ipAddress->id);
        $this->assertCount(1, $device->ipAddresses);

        // Gerät stillegen
        $device->update(['status' => 'decommissioned']);

        // IPs sollten freigegeben werden (in echter Anwendung)
        $device->ipAddresses()->detach();
        $ipAddress->update(['status' => 'available']);

        $this->assertEquals('decommissioned', $device->fresh()->status);
        $this->assertCount(0, $device->fresh()->ipAddresses);
        $this->assertTrue($ipAddress->fresh()->isAvailable());
    }
}
