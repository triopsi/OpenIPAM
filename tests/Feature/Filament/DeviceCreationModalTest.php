<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\Subnets;
use App\Filament\Resources\Devices\DeviceResource;
use App\Filament\Resources\Devices\Pages\ListDevices;
use App\Models\Device;
use App\Models\IpAddress;
use App\Models\User;
use App\Services\DeviceCreationService;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/** Verify native resource and inline device dialogs without separate creation pages. */
class DeviceCreationModalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Open device creation from the list instead of navigating to a CreateRecord page.
     *
     * Expect: The creation action opens a modal with no destination URL and the resource has no create page.
     */
    public function test_device_creation_opens_a_native_modal(): void
    {
        $this->actingAs(User::factory()->withoutTwoFactor()->create());
        $this->assertFalse(DeviceResource::hasPage('create'));
        $page = Livewire::test(ListDevices::class)->mountAction('create')
            ->assertActionMounted('create')->assertSee(__('devices.fields.name'));
        $this->assertNull($page->instance()->getMountedAction()->getUrl());
    }

    /**
     * Create a device with explicitly selected primary and additional addresses.
     *
     * Expect: Saving the modal persists both assignments with the correct primary flags and stays on the list.
     */
    public function test_modal_creation_preserves_ip_assignment_fields(): void
    {
        $this->actingAs(User::factory()->withoutTwoFactor()->create());
        $primary = IpAddress::factory()->create(['status' => 'available']);
        $additional = IpAddress::factory()->create(['status' => 'available']);
        Livewire::test(ListDevices::class)->callAction('create', data: [
            'name' => 'Modal router', 'status' => 'active',
            'primary_ip_address_id' => $primary->id, 'additional_ip_addresses' => [$additional->id],
        ])->assertHasNoActionErrors()->assertNoRedirect();

        $device = Device::sole();
        $this->assertSame($primary->id, $device->primaryIpAddress()->id);
        $this->assertDatabaseHas('device_ip_address', ['device_id' => $device->id, 'ip_address_id' => $additional->id, 'is_primary' => false]);
        $this->assertSame('assigned', $primary->fresh()->status);
        $this->assertSame('assigned', $additional->fresh()->status);
    }

    /**
     * Reserve an address while the device creation modal is already open.
     *
     * Expect: Saving reports a field error and leaves neither a new device nor partial address assignments.
     */
    public function test_stale_ip_selection_does_not_create_a_partial_device(): void
    {
        $this->actingAs(User::factory()->withoutTwoFactor()->create());
        $ip = IpAddress::factory()->create(['status' => 'available']);
        $page = Livewire::test(ListDevices::class)->mountAction('create')
            ->fillForm(['name' => 'Stale router', 'primary_ip_address_id' => $ip->id], 'mountedActionSchema0');
        $ip->update(['status' => 'reserved']);
        $page->callMountedAction()->assertHasActionErrors(['primary_ip_address_id']);
        $this->assertDatabaseCount('devices', 0);
        $this->assertDatabaseCount('device_ip_address', 0);
        $this->assertSame('reserved', $ip->fresh()->status);
    }

    /**
     * Create a device inline while a subnet assignment modal remains open.
     *
     * Expect: The nested modal selects the new device without allocating a default IP, and the parent action assigns only the chosen host.
     */
    public function test_inline_device_creation_returns_to_the_subnet_assignment(): void
    {
        $this->actingAs(User::factory()->withoutTwoFactor()->create());
        $unrelated = IpAddress::factory()->create(['ip_address' => '10.0.0.99', 'version' => 4, 'status' => 'available']);
        $page = Livewire::withQueryParams(['subnet' => '10.0.0.0/24'])->test(Subnets::class)
            ->mountAction('assignAddress', ['address' => '10.0.0.42'])
            ->callAction(TestAction::make('createOption')->schemaComponent('device_id'), data: [
                'name' => 'Inline server', 'hostname' => 'inline.example.test', 'status' => 'active',
            ])->assertHasNoActionErrors()->assertActionMounted('assignAddress');

        $device = Device::sole();
        $page->assertSet('mountedActions.0.data.device_id', $device->id);
        $this->assertDatabaseCount('ip_addresses', 1);
        $this->assertDatabaseCount('device_ip_address', 0);
        $this->assertSame('available', $unrelated->fresh()->status);

        $page->callMountedAction()->assertHasNoActionErrors()->assertSee('10.0.0.42 — Assigned — Inline server');
        $this->assertDatabaseHas('device_ip_address', ['device_id' => $device->id, 'ip_address_id' => IpAddress::where('ip_address', '10.0.0.42')->sole()->id]);
        $this->assertSame('available', $unrelated->fresh()->status);
    }

    /**
     * Submit invalid nested device data while assigning a subnet address.
     *
     * Expect: Missing names and invalid service URLs produce validation errors without creating a device or IP record.
     */
    public function test_inline_device_creation_validates_shared_fields(): void
    {
        $this->actingAs(User::factory()->withoutTwoFactor()->create());
        Livewire::withQueryParams(['subnet' => '10.0.0.0/24'])->test(Subnets::class)
            ->mountAction('assignAddress', ['address' => '10.0.0.42'])
            ->callAction(TestAction::make('createOption')->schemaComponent('device_id'), data: [
                'name' => '', 'url' => 'not-a-url',
            ])->assertHasActionErrors(['name' => 'required', 'url' => 'url']);
        $this->assertDatabaseCount('devices', 0);
        $this->assertDatabaseCount('ip_addresses', 0);
    }

    /**
     * Cancel nested creation and then cancel an assignment after creating a device.
     *
     * Expect: Cancellation creates no unwanted IP assignment; a device saved explicitly remains available independently.
     */
    public function test_cancelling_dialogs_does_not_allocate_addresses(): void
    {
        $this->actingAs(User::factory()->withoutTwoFactor()->create());
        $page = Livewire::withQueryParams(['subnet' => '10.0.0.0/24'])->test(Subnets::class)
            ->mountAction('assignAddress', ['address' => '10.0.0.42'])
            ->mountAction(TestAction::make('createOption')->schemaComponent('device_id'))
            ->call('unmountAction')->assertActionMounted('assignAddress');
        $this->assertDatabaseCount('devices', 0);

        $page->callAction(TestAction::make('createOption')->schemaComponent('device_id'), data: ['name' => 'Saved device'])
            ->assertHasNoActionErrors()->call('unmountAction');
        $this->assertDatabaseCount('devices', 1);
        $this->assertDatabaseCount('ip_addresses', 0);
        $this->assertDatabaseCount('device_ip_address', 0);
    }

    /**
     * Reject an occupied additional address when another selected address is still free.
     *
     * Expect: The transactional creation service preserves both original statuses and creates no partial device or link.
     */
    public function test_creation_service_rejects_stale_additional_addresses_atomically(): void
    {
        $primary = IpAddress::factory()->create(['status' => 'available']);
        $additional = IpAddress::factory()->create(['status' => 'assigned']);
        try {
            app(DeviceCreationService::class)->create([
                'name' => 'Partial device', 'primary_ip_address_id' => $primary->id,
                'additional_ip_addresses' => [$additional->id],
            ]);
            $this->fail('An occupied additional IP was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('additional_ip_addresses', $exception->errors());
        }
        $this->assertDatabaseCount('devices', 0);
        $this->assertDatabaseCount('device_ip_address', 0);
        $this->assertSame('available', $primary->fresh()->status);
        $this->assertSame('assigned', $additional->fresh()->status);
    }
}
