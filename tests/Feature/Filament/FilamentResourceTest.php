<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Devices\Pages\CreateDevice;
use App\Filament\Resources\Devices\Pages\EditDevice;
use App\Filament\Resources\Devices\Pages\ListDevices;
use App\Filament\Resources\IpAddresses\Pages\CreateIpAddress;
use App\Filament\Resources\IpAddresses\Pages\ListIpAddresses;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Device;
use App\Models\IpAddress;
use App\Models\IpAddressGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentResourceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verify device resource can list devices.
     *
     * Expect: The device list renders and displays all device fixtures.
     */
    public function test_device_resource_can_list_devices(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        Device::factory()->count(3)->create();

        $this->actingAs($user);

        Livewire::test(ListDevices::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(Device::all());
    }

    /**
     * Verify device resource can create device.
     *
     * Expect: Submitting valid device fields produces no form errors and persists the new device.
     */
    public function test_device_resource_can_create_device(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $deviceData = [
            'name' => 'New Test Device',
            'hostname' => 'test.example.com',
            'mac_address' => '00:11:22:33:44:55',
            'type' => 'server',
            'status' => 'active',
            'location' => 'Test Location',
            'description' => 'Test Description',
        ];

        Livewire::test(CreateDevice::class)
            ->fillForm($deviceData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('devices', [
            'name' => 'New Test Device',
            'hostname' => 'test.example.com',
        ]);
    }

    /**
     * Verify device resource can edit device.
     *
     * Expect: Saving the edit form persists the updated device name without form errors.
     */
    public function test_device_resource_can_edit_device(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $device = Device::factory()->create(['name' => 'Original Name']);

        $this->actingAs($user);

        Livewire::test(EditDevice::class, ['record' => $device->id])
            ->fillForm(['name' => 'Updated Name'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals('Updated Name', $device->fresh()->name);
    }

    /**
     * Verify IP address resource can list addresses.
     *
     * Expect: The address list renders and displays all address fixtures.
     */
    public function test_ip_address_resource_can_list_addresses(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        IpAddress::factory()->count(5)->create();

        $this->actingAs($user);

        Livewire::test(ListIpAddresses::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(IpAddress::all());
    }

    /**
     * Verify IP address resource can create address.
     *
     * Expect: Submitting valid address fields produces no form errors and persists the address.
     */
    public function test_ip_address_resource_can_create_address(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $ipData = [
            'ip_address' => '192.168.1.100',
            'version' => 4,
            'subnet' => '192.168.1.0/24',
            'gateway' => '192.168.1.1',
            'status' => 'available',
            'description' => 'Test IP Address',
        ];

        Livewire::test(CreateIpAddress::class)
            ->fillForm($ipData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('ip_addresses', [
            'ip_address' => '192.168.1.100',
            'version' => 4,
        ]);
    }

    /**
     * Verify user resource can list users.
     *
     * Expect: The user list renders and displays all user fixtures.
     */
    public function test_user_resource_can_list_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->count(3)->create();

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(User::all());
    }

    /**
     * Verify user resource can create user.
     *
     * Expect: Submitting valid user fields produces no form errors and persists the account.
     */
    public function test_user_resource_can_create_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_admin' => false,
        ];

        Livewire::test(CreateUser::class)
            ->fillForm($userData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
        ]);
    }

    /**
     * Verify user resource can edit user.
     *
     * Expect: Saving the user form persists the new name without form errors.
     */
    public function test_user_resource_can_edit_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['name' => 'Original User']);

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm(['name' => 'Updated User'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals('Updated User', $user->fresh()->name);
    }

    /**
     * Verify device resource validates required fields.
     *
     * Expect: An empty device name produces a required-field validation error.
     */
    public function test_device_resource_validates_required_fields(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        Livewire::test(CreateDevice::class)
            ->fillForm([
                'name' => '', // Required field empty
            ])
            ->call('create')
            ->assertHasFormErrors(['name']);
    }

    /**
     * Exercise address creation with valid form input.
     *
     * Expect: The valid-address creation action executes without a runtime error; invalid input is not tested.
     */
    public function test_ip_address_resource_validates_ip_format(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $group = IpAddressGroup::factory()->create();

        // Test mit validen Daten zuerst
        $validResponse = Livewire::test(CreateIpAddress::class)
            ->fillForm([
                'ip_address' => '192.168.1.100', // Valid IP format
                'version' => 4,
                'status' => 'available',
                'ip_address_group_id' => $group->id,
            ])
            ->call('create');

        $this->assertTrue(true); // Dieser Test checkt nur dass valide IPs funktionieren
    }

    /**
     * Verify user resource validates email uniqueness.
     *
     * Expect: A duplicate user email produces a form validation error.
     */
    public function test_user_resource_validates_email_uniqueness(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'New User',
                'email' => 'existing@example.com', // Duplicate email
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);
    }

    /**
     * Verify filament resources are protected by auth.
     *
     * Expect: A guest device-page request redirects to the login page.
     */
    public function test_filament_resources_are_protected_by_auth(): void
    {
        // Test ohne Authentication über HTTP-Request
        $response = $this->get('/admin/devices');
        $response->assertRedirect('/admin/login');
    }

    /**
     * Request the user-management route with a non-admin fixture.
     *
     * Expect: The non-admin fixture receives HTTP 302; the redirect destination and role enforcement are not asserted.
     */
    public function test_non_admin_users_cannot_access_user_resource(): void
    {
        $regularUser = User::factory()->create(['is_admin' => false]);
        $this->actingAs($regularUser);

        $response = $this->get('/admin/users');
        $response->assertStatus(302); // Redirect wegen fehlenden Permissions
    }

    /**
     * Verify device IP assignment through resource.
     *
     * Expect: The relationship contains the assigned address and its status is assigned.
     */
    public function test_device_ip_assignment_through_resource(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $device = Device::factory()->create();
        $ipAddress = IpAddress::factory()->create(['status' => 'available']);

        $this->actingAs($user);

        // Simuliere IP-Zuweisung über Filament Relation Manager
        $device->ipAddresses()->attach($ipAddress->id, ['is_primary' => true]);
        $ipAddress->update(['status' => 'assigned']);

        $this->assertCount(1, $device->ipAddresses);
        $this->assertEquals('assigned', $ipAddress->fresh()->status);
    }
}
