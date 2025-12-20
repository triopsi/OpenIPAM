<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\IpAddress;
use App\Models\IpAddressGroup;
use App\Services\IpAssignmentService;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // IP-Adress Gruppen erstellen
        $serverGroup = IpAddressGroup::create([
            'name' => 'Server Network',
            'description' => 'Productive server infrastructure',
            'color' => '#3B82F6',
        ]);

        $workstationGroup = IpAddressGroup::create([
            'name' => 'Workstations',
            'description' => 'Employee workstations and laptops',
            'color' => '#10B981',
        ]);

        $infrastructureGroup = IpAddressGroup::create([
            'name' => 'Network Infrastructure',
            'description' => 'Switches, routers and access points',
            'color' => '#F59E0B',
        ]);

        // Server IP-Adressen
        $serverIps = [];
        for ($i = 10; $i <= 20; $i++) {
            $serverIps[] = IpAddress::create([
                'ip_address' => "192.168.1.$i",
                'version' => 4,
                'status' => 'available',
                'subnet' => '192.168.1.0/24',
                'gateway' => '192.168.1.1',
                'group_id' => $serverGroup->id,
                'description' => "Server IP $i",
            ]);
        }

        // Workstation IP-Adressen
        $workstationIps = [];
        for ($i = 100; $i <= 120; $i++) {
            $workstationIps[] = IpAddress::create([
                'ip_address' => "192.168.2.$i",
                'version' => 4,
                'status' => 'available',
                'subnet' => '192.168.2.0/24',
                'gateway' => '192.168.2.1',
                'group_id' => $workstationGroup->id,
                'description' => "Workstation IP $i",
            ]);
        }

        // Infrastruktur IP-Adressen
        $infraIps = [];
        for ($i = 1; $i <= 10; $i++) {
            $infraIps[] = IpAddress::create([
                'ip_address' => "192.168.0.$i",
                'version' => 4,
                'status' => 'available',
                'subnet' => '192.168.0.0/24',
                'gateway' => '192.168.0.1',
                'group_id' => $infrastructureGroup->id,
                'description' => "Infrastructure IP $i",
            ]);
        }

        // Ungrouped IPs
        $ungroupedIps = [];
        for ($i = 50; $i <= 55; $i++) {
            $ungroupedIps[] = IpAddress::create([
                'ip_address' => "10.0.0.$i",
                'version' => 4,
                'status' => 'available',
                'subnet' => '10.0.0.0/24',
                'gateway' => '10.0.0.1',
                'group_id' => null,
                'description' => "Legacy IP $i",
            ]);
        }

        $ipService = new IpAssignmentService;

        // Server erstellen
        $webServer = Device::create([
            'name' => 'Web Server 01',
            'hostname' => 'web01.company.com',
            'type' => 'server',
            'status' => 'active',
            'location' => 'Datacenter Rack A1',
            'mac_address' => '00:50:56:12:34:56',
            'description' => 'Primary web application server',
        ]);
        $ipService->assignIpToDevice($webServer->id, $serverIps[0]->id, true);
        $ipService->assignIpToDevice($webServer->id, $serverIps[1]->id, false);

        $dbServer = Device::create([
            'name' => 'Database Server',
            'hostname' => 'db01.company.com',
            'type' => 'server',
            'status' => 'active',
            'location' => 'Datacenter Rack A2',
            'mac_address' => '00:50:56:78:90:12',
            'description' => 'MySQL database server',
        ]);
        $ipService->assignIpToDevice($dbServer->id, $serverIps[2]->id, true);

        $fileServer = Device::create([
            'name' => 'File Server',
            'hostname' => 'files.company.com',
            'type' => 'server',
            'status' => 'maintenance',
            'location' => 'Datacenter Rack B1',
            'mac_address' => '00:50:56:34:56:78',
            'description' => 'Central file storage server',
        ]);
        $ipService->assignIpToDevice($fileServer->id, $serverIps[3]->id, true);

        // Workstations
        $workstation1 = Device::create([
            'name' => 'WS-Marketing-01',
            'hostname' => 'ws-mkt-01',
            'type' => 'workstation',
            'status' => 'active',
            'location' => 'Office Floor 2',
            'mac_address' => '00:11:22:33:44:55',
            'description' => 'Marketing team workstation',
        ]);
        $ipService->assignIpToDevice($workstation1->id, $workstationIps[0]->id, true);

        $laptop1 = Device::create([
            'name' => 'Laptop-Dev-02',
            'hostname' => 'laptop-dev-02',
            'type' => 'laptop',
            'status' => 'active',
            'location' => 'Home Office',
            'mac_address' => '00:AA:BB:CC:DD:EE',
            'description' => 'Developer laptop',
        ]);
        $ipService->assignIpToDevice($laptop1->id, $workstationIps[1]->id, true);

        // Network Infrastructure
        $coreSwitch = Device::create([
            'name' => 'Core Switch 01',
            'hostname' => 'sw-core-01',
            'type' => 'switch',
            'status' => 'active',
            'location' => 'Network Cabinet',
            'mac_address' => '00:1B:2C:3D:4E:5F',
            'description' => '48-port gigabit core switch',
        ]);
        $ipService->assignIpToDevice($coreSwitch->id, $infraIps[0]->id, true);

        $router = Device::create([
            'name' => 'Edge Router',
            'hostname' => 'rtr-edge-01',
            'type' => 'router',
            'status' => 'active',
            'location' => 'Network Cabinet',
            'mac_address' => '00:2C:3D:4E:5F:60',
            'description' => 'Main internet gateway router',
        ]);
        $ipService->assignIpToDevice($router->id, $infraIps[1]->id, true);

        $accessPoint = Device::create([
            'name' => 'WiFi AP Office',
            'hostname' => 'ap-office-01',
            'type' => 'access_point',
            'status' => 'active',
            'location' => 'Office Ceiling',
            'mac_address' => '00:3D:4E:5F:60:71',
            'description' => 'Office wireless access point',
        ]);
        $ipService->assignIpToDevice($accessPoint->id, $infraIps[2]->id, true);

        // Printer
        $printer = Device::create([
            'name' => 'Office Printer HP',
            'hostname' => 'printer-office',
            'type' => 'printer',
            'status' => 'active',
            'location' => 'Print Room',
            'mac_address' => '00:4E:5F:60:71:82',
            'description' => 'HP LaserJet office printer',
        ]);
        $ipService->assignIpToDevice($printer->id, $ungroupedIps[0]->id, true);

        // Device ohne IP
        Device::create([
            'name' => 'Backup Server (Offline)',
            'hostname' => 'backup-server',
            'type' => 'server',
            'status' => 'inactive',
            'location' => 'Storage Room',
            'mac_address' => '00:5F:60:71:82:93',
            'description' => 'Offline backup server awaiting setup',
        ]);

        // Unbekanntes Device ohne IP
        Device::create([
            'name' => 'Unknown Device',
            'type' => 'other',
            'status' => 'inactive',
            'description' => 'Unidentified network device found during scan',
        ]);
    }
}
