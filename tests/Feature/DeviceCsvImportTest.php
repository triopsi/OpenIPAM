<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\IpAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeviceCsvImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user for authentication
        $this->user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // Set up fake storage
        Storage::fake('local');
    }

    #[Test]
    public function it_can_import_devices_from_csv_with_header()
    {
        $csvContent = "Name,Hostname,MAC Address,Type,Location,Status,URL,Description\n".
                      "Router-01,router01.example.com,00:1A:2B:3C:4D:5E,router,Server Room,active,http://router01.example.com,Main gateway router\n".
                      "Switch-01,switch01.example.com,00:1A:2B:3C:4D:5F,switch,Server Room,active,,24-port gigabit switch\n".
                      'Firewall-01,firewall01.example.com,00:1A:2B:3C:4D:60,firewall,DMZ,active,https://firewall01.example.com,Main security appliance';

        $csvFile = UploadedFile::fake()->createWithContent('devices.csv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test-devices.csv');

        $importData = [
            'csv_file' => 'csv-imports/test-devices.csv',
            'delimiter' => ',',
            'has_header' => true,
            'mapping_name' => 'col_0',
            'mapping_hostname' => 'col_1',
            'mapping_mac_address' => 'col_2',
            'mapping_type' => 'col_3',
            'mapping_location' => 'col_4',
            'mapping_status' => 'col_5',
            'mapping_url' => 'col_6',
            'mapping_description' => 'col_7',
            'duplicate_handling' => 'skip',
        ];

        $this->actingAs($this->user);

        // Execute the import logic manually since we're testing the service logic
        $this->executeImport($importData);

        // Assert devices were created
        $this->assertDatabaseCount('devices', 3);

        $router = Device::where('name', 'Router-01')->first();
        $this->assertNotNull($router);
        $this->assertEquals('router01.example.com', $router->hostname);
        $this->assertEquals('00:1A:2B:3C:4D:5E', $router->mac_address);
        $this->assertEquals('router', $router->type);
        $this->assertEquals('Server Room', $router->location);
        $this->assertEquals('active', $router->status);
        $this->assertEquals('http://router01.example.com', $router->url);
        $this->assertEquals('Main gateway router', $router->description);

        $switch = Device::where('name', 'Switch-01')->first();
        $this->assertNotNull($switch);
        $this->assertNull($switch->url); // Empty URL should be null
    }

    #[Test]
    public function it_can_import_devices_from_csv_without_header()
    {
        $csvContent = "Router-02,router02.example.com,00:1A:2B:3C:4D:6A,router,Data Center,active,http://router02.example.com,Secondary router\n".
                      'Switch-02,switch02.example.com,00:1A:2B:3C:4D:6B,switch,Data Center,inactive,,Secondary switch';

        $csvFile = UploadedFile::fake()->createWithContent('devices.csv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test-devices-no-header.csv');

        $importData = [
            'csv_file' => 'csv-imports/test-devices-no-header.csv',
            'delimiter' => ',',
            'has_header' => false,
            'mapping_name' => 'col_0',
            'mapping_hostname' => 'col_1',
            'mapping_mac_address' => 'col_2',
            'mapping_type' => 'col_3',
            'mapping_location' => 'col_4',
            'mapping_status' => 'col_5',
            'mapping_url' => 'col_6',
            'mapping_description' => 'col_7',
            'duplicate_handling' => 'skip',
        ];

        $this->actingAs($this->user);
        $this->executeImport($importData);

        $this->assertDatabaseCount('devices', 2);

        $router = Device::where('name', 'Router-02')->first();
        $this->assertNotNull($router);
        $this->assertEquals('router02.example.com', $router->hostname);
    }

    #[Test]
    public function it_handles_different_delimiters()
    {
        // Test with semicolon delimiter
        $csvContent = "Name;Hostname;Type\n".
                      "Router-03;router03.example.com;router\n".
                      'Switch-03;switch03.example.com;switch';

        $csvFile = UploadedFile::fake()->createWithContent('devices.csv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test-devices-semicolon.csv');

        $importData = [
            'csv_file' => 'csv-imports/test-devices-semicolon.csv',
            'delimiter' => ';',
            'has_header' => true,
            'mapping_name' => 'col_0',
            'mapping_hostname' => 'col_1',
            'mapping_type' => 'col_2',
            'duplicate_handling' => 'skip',
        ];

        $this->actingAs($this->user);
        $this->executeImport($importData);

        $this->assertDatabaseCount('devices', 2);

        $router = Device::where('name', 'Router-03')->first();
        $this->assertNotNull($router);
        $this->assertEquals('router03.example.com', $router->hostname);
        $this->assertEquals('router', $router->type);
    }

    #[Test]
    public function it_skips_duplicates_when_skip_mode_is_selected()
    {
        // First, create an existing device
        Device::create([
            'name' => 'Router-01',
            'hostname' => 'old-router.example.com',
            'type' => 'router',
            'status' => 'active',
        ]);

        $csvContent = "Name,Hostname,Type\n".
                      "Router-01,new-router.example.com,switch\n".
                      'Router-02,router02.example.com,router';

        $csvFile = UploadedFile::fake()->createWithContent('devices.csv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test-devices-duplicates.csv');

        $importData = [
            'csv_file' => 'csv-imports/test-devices-duplicates.csv',
            'delimiter' => ',',
            'has_header' => true,
            'mapping_name' => 'col_0',
            'mapping_hostname' => 'col_1',
            'mapping_type' => 'col_2',
            'duplicate_handling' => 'skip',
        ];

        $this->actingAs($this->user);
        $this->executeImport($importData);

        $this->assertDatabaseCount('devices', 2);

        // Original device should remain unchanged
        $router = Device::where('name', 'Router-01')->first();
        $this->assertEquals('old-router.example.com', $router->hostname);
        $this->assertEquals('router', $router->type);

        // New device should be created
        $this->assertDatabaseHas('devices', [
            'name' => 'Router-02',
            'hostname' => 'router02.example.com',
        ]);
    }

    #[Test]
    public function it_overwrites_duplicates_when_overwrite_mode_is_selected()
    {
        // First, create an existing device
        Device::create([
            'name' => 'Router-01',
            'hostname' => 'old-router.example.com',
            'type' => 'router',
            'status' => 'active',
            'description' => 'Old description',
        ]);

        $csvContent = "Name,Hostname,Type,Description\n".
                      'Router-01,new-router.example.com,switch,New description';

        $csvFile = UploadedFile::fake()->createWithContent('devices.csv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test-devices-overwrite.csv');

        $importData = [
            'csv_file' => 'csv-imports/test-devices-overwrite.csv',
            'delimiter' => ',',
            'has_header' => true,
            'mapping_name' => 'col_0',
            'mapping_hostname' => 'col_1',
            'mapping_type' => 'col_2',
            'mapping_description' => 'col_3',
            'duplicate_handling' => 'overwrite',
        ];

        $this->actingAs($this->user);
        $this->executeImport($importData);

        $this->assertDatabaseCount('devices', 1);

        // Device should be completely overwritten
        $router = Device::where('name', 'Router-01')->first();
        $this->assertEquals('new-router.example.com', $router->hostname);
        $this->assertEquals('switch', $router->type);
        $this->assertEquals('New description', $router->description);
    }

    #[Test]
    public function it_merges_duplicates_when_merge_mode_is_selected()
    {
        // First, create an existing device with some filled and some empty fields
        Device::create([
            'name' => 'Router-01',
            'hostname' => 'old-router.example.com',
            'type' => 'router',
            'status' => 'active',
            'location' => '', // Empty field
            'description' => null, // Empty field
        ]);

        $csvContent = "Name,Hostname,Type,Location,Description\n".
                      'Router-01,new-router.example.com,switch,Server Room,New description';

        $csvFile = UploadedFile::fake()->createWithContent('devices.csv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test-devices-merge.csv');

        $importData = [
            'csv_file' => 'csv-imports/test-devices-merge.csv',
            'delimiter' => ',',
            'has_header' => true,
            'mapping_name' => 'col_0',
            'mapping_hostname' => 'col_1',
            'mapping_type' => 'col_2',
            'mapping_location' => 'col_3',
            'mapping_description' => 'col_4',
            'duplicate_handling' => 'merge',
        ];

        $this->actingAs($this->user);
        $this->executeImport($importData);

        $this->assertDatabaseCount('devices', 1);

        // Device should keep existing values but fill empty fields
        $router = Device::where('name', 'Router-01')->first();
        $this->assertEquals('old-router.example.com', $router->hostname); // Kept existing
        $this->assertEquals('router', $router->type); // Kept existing
        $this->assertEquals('Server Room', $router->location); // Filled empty field
        $this->assertEquals('New description', $router->description); // Filled empty field
    }

    #[Test]
    public function it_sets_default_values_for_missing_fields()
    {
        $csvContent = "Name\n".
                      "Router-01\n".
                      'Switch-01';

        $csvFile = UploadedFile::fake()->createWithContent('devices.csv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test-devices-minimal.csv');

        $importData = [
            'csv_file' => 'csv-imports/test-devices-minimal.csv',
            'delimiter' => ',',
            'has_header' => true,
            'mapping_name' => 'col_0',
            'duplicate_handling' => 'skip',
        ];

        $this->actingAs($this->user);
        $this->executeImport($importData);

        $this->assertDatabaseCount('devices', 2);

        $router = Device::where('name', 'Router-01')->first();
        $this->assertEquals('active', $router->status); // Default status
        $this->assertEquals('other', $router->type); // Default type
    }

    #[Test]
    public function it_skips_rows_without_name()
    {
        $csvContent = "Name,Hostname\n".
                      ",router01.example.com\n".  // Empty name
                      "Router-01,router02.example.com\n".
                      '   ,router03.example.com'; // Whitespace only name

        $csvFile = UploadedFile::fake()->createWithContent('devices.csv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test-devices-empty-names.csv');

        $importData = [
            'csv_file' => 'csv-imports/test-devices-empty-names.csv',
            'delimiter' => ',',
            'has_header' => true,
            'mapping_name' => 'col_0',
            'mapping_hostname' => 'col_1',
            'duplicate_handling' => 'skip',
        ];

        $this->actingAs($this->user);
        $this->executeImport($importData);

        // Should only create one device (the one with a valid name)
        $this->assertDatabaseCount('devices', 1);
        $this->assertDatabaseHas('devices', [
            'name' => 'Router-01',
            'hostname' => 'router02.example.com',
        ]);
    }

    #[Test]
    public function it_handles_tab_delimiter()
    {
        $csvContent = "Name\tHostname\tType\n".
                      "Router-01\trouter01.example.com\trouter\n".
                      "Switch-01\tswitch01.example.com\tswitch";

        $csvFile = UploadedFile::fake()->createWithContent('devices.tsv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test-devices-tab.tsv');

        $importData = [
            'csv_file' => 'csv-imports/test-devices-tab.tsv',
            'delimiter' => '\t', // Tab delimiter
            'has_header' => true,
            'mapping_name' => 'col_0',
            'mapping_hostname' => 'col_1',
            'mapping_type' => 'col_2',
            'duplicate_handling' => 'skip',
        ];

        $this->actingAs($this->user);
        $this->executeImport($importData);

        $this->assertDatabaseCount('devices', 2);

        $router = Device::where('name', 'Router-01')->first();
        $this->assertEquals('router01.example.com', $router->hostname);
        $this->assertEquals('router', $router->type);
    }

    #[Test]
    public function it_ignores_unmapped_columns()
    {
        $csvContent = "Name,Ignore1,Hostname,Ignore2,Type\n".
                      'Router-01,ignore_value1,router01.example.com,ignore_value2,router';

        $csvFile = UploadedFile::fake()->createWithContent('devices.csv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test-devices-ignore.csv');

        $importData = [
            'csv_file' => 'csv-imports/test-devices-ignore.csv',
            'delimiter' => ',',
            'has_header' => true,
            'mapping_name' => 'col_0',
            'mapping_hostname' => 'col_2', // Skip col_1
            'mapping_type' => 'col_4', // Skip col_3
            'duplicate_handling' => 'skip',
        ];

        $this->actingAs($this->user);
        $this->executeImport($importData);

        $this->assertDatabaseCount('devices', 1);

        $router = Device::where('name', 'Router-01')->first();
        $this->assertEquals('router01.example.com', $router->hostname);
        $this->assertEquals('router', $router->type);
    }

    /**
     * Simulate the import execution logic from ListDevices
     */
    private function executeImport(array $data): void
    {
        $filePath = Storage::path($data['csv_file']);

        $csv = \League\Csv\Reader::createFromPath($filePath, 'r');
        $delimiter = $data['delimiter'] === '\t' ? "\t" : $data['delimiter'];
        $csv->setDelimiter($delimiter);

        // Wenn Header vorhanden, erste Zeile überspringen
        if ($data['has_header']) {
            $csv->setHeaderOffset(0);
            $records = $csv->getRecords();
        } else {
            $records = $csv->getRecords();
        }

        // Mapping erstellen
        $fieldMapping = [];
        foreach ($data as $key => $value) {
            if (strpos($key, 'mapping_') === 0 && $value !== 'ignore' && ! empty($value)) {
                $field = str_replace('mapping_', '', $key);
                $columnIndex = (int) str_replace('col_', '', $value);
                $fieldMapping[$field] = $columnIndex;
            }
        }

        $duplicateHandling = $data['duplicate_handling'];

        foreach ($records as $rowIndex => $record) {
            $recordArray = array_values((array) $record);
            $deviceData = [];
            $primaryIp = null;
            $secondaryIps = [];

            // Daten aus CSV-Zeile extrahieren
            foreach ($fieldMapping as $field => $columnIndex) {
                if (isset($recordArray[$columnIndex])) {
                    $value = trim($recordArray[$columnIndex]);
                    if (! empty($value)) {
                        if ($field === 'primary_ip') {
                            $primaryIp = $value;
                        } elseif ($field === 'secondary_ips') {
                            // Mehrere IPs durch Semikolon getrennt
                            $secondaryIps = array_filter(array_map('trim', explode(';', $value)));
                        } else {
                            $deviceData[$field] = $value;
                        }
                    }
                }
            }

            // Name ist Pflichtfeld
            if (empty($deviceData['name'])) {
                continue;
            }

            // Prüfen ob Gerät bereits existiert
            $existingDevice = Device::where('name', $deviceData['name'])->first();
            $device = null;

            if ($existingDevice) {
                switch ($duplicateHandling) {
                    case 'skip':
                        continue 2;

                    case 'overwrite':
                        $existingDevice->update($deviceData);
                        $device = $existingDevice;
                        break;

                    case 'merge':
                        foreach ($deviceData as $key => $value) {
                            if (empty($existingDevice->$key)) {
                                $existingDevice->$key = $value;
                            }
                        }
                        $existingDevice->save();
                        $device = $existingDevice;
                        break;
                }
            } else {
                // Standard-Werte setzen
                if (! isset($deviceData['status'])) {
                    $deviceData['status'] = 'active';
                }
                if (! isset($deviceData['type'])) {
                    $deviceData['type'] = 'other';
                }

                $device = Device::create($deviceData);
            }

            // IP-Adressen verarbeiten, falls Gerät erstellt/aktualisiert wurde
            if ($device) {
                // Primäre IP-Adresse hinzufügen
                if ($primaryIp && $this->isValidIpAddress($primaryIp)) {
                    $ipRecord = $this->createOrGetIpAddress($primaryIp);
                    if ($ipRecord) {
                        // Entferne alle bestehenden primären IP-Zuordnungen wenn overwrite
                        if ($duplicateHandling === 'overwrite') {
                            $device->ipAddresses()->updateExistingPivot(
                                $device->ipAddresses->pluck('id')->toArray(),
                                ['is_primary' => false]
                            );
                        }

                        // Zuordnung erstellen/aktualisieren
                        $device->ipAddresses()->syncWithoutDetaching([
                            $ipRecord->id => ['is_primary' => true],
                        ]);
                    }
                }

                // Sekundäre IP-Adressen hinzufügen
                foreach ($secondaryIps as $secondaryIp) {
                    if ($this->isValidIpAddress($secondaryIp)) {
                        $ipRecord = $this->createOrGetIpAddress($secondaryIp);
                        if ($ipRecord) {
                            $device->ipAddresses()->syncWithoutDetaching([
                                $ipRecord->id => ['is_primary' => false],
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Validate if string is a valid IPv4 or IPv6 address
     */
    private function isValidIpAddress(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Create or get existing IP address record
     */
    private function createOrGetIpAddress(string $ipAddress): ?IpAddress
    {
        try {
            // Check if IP already exists
            $existingIp = IpAddress::where('ip_address', $ipAddress)->first();

            if ($existingIp) {
                return $existingIp;
            }

            // Determine IP version
            $version = filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 4 : 6;

            // Create new IP address record
            return IpAddress::create([
                'ip_address' => $ipAddress,
                'version' => $version,
                'status' => 'assigned',
                'group' => 'imported',
                'description' => 'Imported via CSV',
            ]);

        } catch (\Exception $e) {
            // Log error but don't fail the whole import
            return null;
        }
    }

    #[Test]
    public function it_can_import_devices_with_primary_ip_addresses()
    {
        $csvContent = "Name,Hostname,Primary IP\n".
                      "Router-01,router01.example.com,192.168.1.1\n".
                      "Server-01,server01.example.com,10.0.0.100\n".
                      'IPv6-Device,ipv6device.example.com,2001:db8::1';

        $csvFile = UploadedFile::fake()->createWithContent('devices.csv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test-devices-ip.csv');

        $importData = [
            'csv_file' => 'csv-imports/test-devices-ip.csv',
            'delimiter' => ',',
            'has_header' => true,
            'mapping_name' => 'col_0',
            'mapping_hostname' => 'col_1',
            'mapping_primary_ip' => 'col_2',
            'duplicate_handling' => 'skip',
        ];

        $this->actingAs($this->user);
        $this->executeImport($importData);

        // Check devices were created
        $this->assertDatabaseCount('devices', 3);
        $this->assertDatabaseCount('ip_addresses', 3);

        // Check router with IPv4
        $router = Device::where('name', 'Router-01')->first();
        $this->assertNotNull($router);
        $routerPrimaryIp = $router->ipAddresses()->wherePivot('is_primary', true)->first();
        $this->assertNotNull($routerPrimaryIp);
        $this->assertEquals('192.168.1.1', $routerPrimaryIp->ip_address);
        $this->assertEquals(4, $routerPrimaryIp->version);

        // Check IPv6 device
        $ipv6Device = Device::where('name', 'IPv6-Device')->first();
        $this->assertNotNull($ipv6Device);
        $ipv6PrimaryIp = $ipv6Device->ipAddresses()->wherePivot('is_primary', true)->first();
        $this->assertNotNull($ipv6PrimaryIp);
        $this->assertEquals('2001:db8::1', $ipv6PrimaryIp->ip_address);
        $this->assertEquals(6, $ipv6PrimaryIp->version);
    }

    #[Test]
    public function it_can_import_devices_with_secondary_ip_addresses()
    {
        $csvContent = "Name,Primary IP,Secondary IPs\n".
                      "Router-01,192.168.1.1,10.0.0.1;172.16.0.1\n".
                      'Server-01,192.168.10.10,2001:db8::1;fe80::1;192.168.20.10';

        $csvFile = UploadedFile::fake()->createWithContent('devices.csv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test-devices-multi-ip.csv');

        $importData = [
            'csv_file' => 'csv-imports/test-devices-multi-ip.csv',
            'delimiter' => ',',
            'has_header' => true,
            'mapping_name' => 'col_0',
            'mapping_primary_ip' => 'col_1',
            'mapping_secondary_ips' => 'col_2',
            'duplicate_handling' => 'skip',
        ];

        $this->actingAs($this->user);
        $this->executeImport($importData);

        // Check devices were created
        $this->assertDatabaseCount('devices', 2);

        // Router should have 3 IPs total (1 primary + 2 secondary)
        $router = Device::where('name', 'Router-01')->first();
        $this->assertNotNull($router);
        $this->assertCount(3, $router->ipAddresses);

        // Check primary IP
        $primaryIp = $router->ipAddresses()->wherePivot('is_primary', true)->first();
        $this->assertEquals('192.168.1.1', $primaryIp->ip_address);

        // Check secondary IPs
        $secondaryIps = $router->ipAddresses()->wherePivot('is_primary', false)->get();
        $this->assertCount(2, $secondaryIps);
        $secondaryIpAddresses = $secondaryIps->pluck('ip_address')->toArray();
        $this->assertContains('10.0.0.1', $secondaryIpAddresses);
        $this->assertContains('172.16.0.1', $secondaryIpAddresses);

        // Server should have 4 IPs total (1 primary + 3 secondary, mixed IPv4/IPv6)
        $server = Device::where('name', 'Server-01')->first();
        $this->assertCount(4, $server->ipAddresses);
    }

    #[Test]
    public function it_skips_invalid_ip_addresses()
    {
        $csvContent = "Name,Primary IP,Secondary IPs\n".
                      "Router-01,192.168.1.1,invalid.ip;10.0.0.1\n".
                      "Router-02,not.an.ip,192.168.1.2\n".
                      'Router-03,192.168.1.3,';

        $csvFile = UploadedFile::fake()->createWithContent('devices.csv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test-devices-invalid-ip.csv');

        $importData = [
            'csv_file' => 'csv-imports/test-devices-invalid-ip.csv',
            'delimiter' => ',',
            'has_header' => true,
            'mapping_name' => 'col_0',
            'mapping_primary_ip' => 'col_1',
            'mapping_secondary_ips' => 'col_2',
            'duplicate_handling' => 'skip',
        ];

        $this->actingAs($this->user);
        $this->executeImport($importData);

        // All devices should be created
        $this->assertDatabaseCount('devices', 3);

        // Router-01 should have 2 valid IPs (primary + 1 valid secondary)
        $router1 = Device::where('name', 'Router-01')->first();
        $this->assertCount(2, $router1->ipAddresses);

        // Router-02 should have 1 IP (invalid primary, but valid secondary)
        $router2 = Device::where('name', 'Router-02')->first();
        $this->assertCount(1, $router2->ipAddresses);
        $this->assertEquals('192.168.1.2', $router2->ipAddresses->first()->ip_address);
        $this->assertEquals(0, $router2->ipAddresses->first()->pivot->is_primary); // Should be secondary (0)

        // Router-03 should have 1 IP (valid primary, no secondary)
        $router3 = Device::where('name', 'Router-03')->first();
        $this->assertCount(1, $router3->ipAddresses);
    }

    #[Test]
    public function it_reuses_existing_ip_addresses()
    {
        // Pre-create an IP address
        $existingIp = IpAddress::create([
            'ip_address' => '192.168.1.1',
            'version' => 4,
            'status' => 'available',
            'group' => 'network1',
            'description' => 'Existing IP',
        ]);

        $csvContent = "Name,Primary IP\n".
                      "Router-01,192.168.1.1\n".
                      'Router-02,192.168.1.1';

        $csvFile = UploadedFile::fake()->createWithContent('devices.csv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test-devices-reuse-ip.csv');

        $importData = [
            'csv_file' => 'csv-imports/test-devices-reuse-ip.csv',
            'delimiter' => ',',
            'has_header' => true,
            'mapping_name' => 'col_0',
            'mapping_primary_ip' => 'col_1',
            'duplicate_handling' => 'skip',
        ];

        $this->actingAs($this->user);
        $this->executeImport($importData);

        // Should only have 1 IP address record (reused)
        $this->assertDatabaseCount('ip_addresses', 1);
        $this->assertDatabaseCount('devices', 2);

        // Both devices should use the same IP
        $router1 = Device::where('name', 'Router-01')->first();
        $router2 = Device::where('name', 'Router-02')->first();

        $this->assertEquals('192.168.1.1', $router1->ipAddresses->first()->ip_address);
        $this->assertEquals('192.168.1.1', $router2->ipAddresses->first()->ip_address);

        // IP should still be the same record
        $this->assertEquals($existingIp->id, $router1->ipAddresses->first()->id);
        $this->assertEquals($existingIp->id, $router2->ipAddresses->first()->id);
    }

    #[Test]
    public function it_handles_overwrite_mode_with_ip_addresses()
    {
        // Create device with existing IP
        $device = Device::create([
            'name' => 'Router-01',
            'hostname' => 'old-router.example.com',
            'type' => 'router',
            'status' => 'active',
        ]);

        $oldIp = IpAddress::create([
            'ip_address' => '192.168.1.100',
            'version' => 4,
            'status' => 'assigned',
            'group' => 'network1',
        ]);

        $device->ipAddresses()->attach($oldIp->id, ['is_primary' => true]);

        $csvContent = "Name,Primary IP\n".
                      'Router-01,192.168.1.1';

        $csvFile = UploadedFile::fake()->createWithContent('devices.csv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test-devices-overwrite-ip.csv');

        $importData = [
            'csv_file' => 'csv-imports/test-devices-overwrite-ip.csv',
            'delimiter' => ',',
            'has_header' => true,
            'mapping_name' => 'col_0',
            'mapping_primary_ip' => 'col_1',
            'duplicate_handling' => 'overwrite',
        ];

        $this->actingAs($this->user);
        $this->executeImport($importData);

        // Should still be 1 device
        $this->assertDatabaseCount('devices', 1);

        $updatedDevice = Device::where('name', 'Router-01')->first();

        // Should have 2 IP addresses now (old one non-primary, new one primary)
        $this->assertCount(2, $updatedDevice->ipAddresses);

        // New IP should be primary
        $primaryIp = $updatedDevice->ipAddresses()->wherePivot('is_primary', true)->first();
        $this->assertEquals('192.168.1.1', $primaryIp->ip_address);

        // Old IP should be non-primary
        $nonPrimaryIp = $updatedDevice->ipAddresses()->wherePivot('is_primary', false)->first();
        $this->assertEquals('192.168.1.100', $nonPrimaryIp->ip_address);
    }
}
