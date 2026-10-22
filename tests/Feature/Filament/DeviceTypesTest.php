<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\Subnets;
use App\Filament\Resources\Devices\Pages\ListDevices;
use App\Models\Device;
use App\Models\DeviceType;
use App\Models\User;
use App\Services\DeviceTypeService;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/** Verify shared, persistent type choices across native device creation contexts. */
class DeviceTypesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Add a type from the device creation modal and reuse it for another device.
     *
     * Expect: The new type is selected automatically and both devices store the same persistent catalog value.
     */
    public function test_new_type_is_selected_and_reusable_by_other_devices(): void
    {
        $this->actingAs(User::factory()->withoutTwoFactor()->create());
        $page = Livewire::test(ListDevices::class)->mountAction('create')
            ->fillForm(['name' => 'Storage one'], 'mountedActionSchema0')
            ->callAction(TestAction::make('createOption')->schemaComponent('type'), data: ['name' => 'NAS'])
            ->assertHasNoActionErrors()->assertActionMounted('create');
        $type = DeviceType::where('name', 'NAS')->sole();
        $page->assertSet('mountedActions.0.data.type', $type->value)
            ->callMountedAction()->assertHasNoActionErrors();

        Livewire::test(ListDevices::class)->callAction('create', data: ['name' => 'Storage two', 'type' => $type->value])
            ->assertHasNoActionErrors();
        $this->assertSame(2, Device::where('type', $type->value)->count());
        $this->assertSame('NAS', app(DeviceTypeService::class)->options()[$type->value]);
    }

    /**
     * Add a type inside inline device creation within a subnet assignment modal.
     *
     * Expect: Closing each nested modal preserves the new type, device and selected subnet address until assignment is saved.
     */
    public function test_type_creation_works_inside_inline_subnet_device_creation(): void
    {
        $this->actingAs(User::factory()->withoutTwoFactor()->create());
        $page = Livewire::withQueryParams(['subnet' => '10.0.0.0/29'])->test(Subnets::class)
            ->mountAction('assignAddress', ['address' => '10.0.0.2'])
            ->mountAction(TestAction::make('createOption')->schemaComponent('device_id'))
            ->fillForm(['name' => 'Inline smart plug'], 'mountedActionSchema1')
            ->callAction(TestAction::make('createOption')->schemaComponent('type'), data: ['name' => 'Smart plug'])
            ->assertHasNoActionErrors();
        $type = DeviceType::where('name', 'Smart plug')->sole();
        $page->assertSet('mountedActions.1.data.type', $type->value)
            ->callMountedAction()->assertHasNoActionErrors()->assertActionMounted('assignAddress');
        $device = Device::sole();
        $this->assertSame($type->value, $device->type);
        $page->callMountedAction()->assertHasNoActionErrors()->assertSee('10.0.0.2 — Assigned — Inline smart plug');
    }

    /**
     * Create repeated names with case or whitespace differences and translated built-in names.
     *
     * Expect: Equivalent names reuse one catalog value, built-ins retain their keys and distinct punctuation names remain distinct.
     */
    public function test_duplicate_names_and_built_in_labels_are_reused(): void
    {
        $service = app(DeviceTypeService::class);
        $nas = $service->create('  NAS  ');
        $this->assertSame($nas, $service->create('nas'));
        $this->assertSame('printer', $service->create(__('devices.types.printer', [], 'de')));
        $this->assertSame('server', $service->create('SERVER'));
        $this->assertDatabaseCount('device_types', 10);
        $this->assertNotSame($nas, $service->create('NAS!'));
    }

    /**
     * Submit invalid custom type names from the nested creation modal.
     *
     * Expect: Empty and overlong names produce field errors without creating catalog entries or devices.
     */
    public function test_custom_type_names_are_validated(): void
    {
        $this->actingAs(User::factory()->withoutTwoFactor()->create());
        foreach (['', str_repeat('x', 101)] as $name) {
            Livewire::test(ListDevices::class)->mountAction('create')
                ->callAction(TestAction::make('createOption')->schemaComponent('type'), data: ['name' => $name])
                ->assertHasActionErrors(['name']);
        }
        $this->assertDatabaseCount('device_types', 9);
        $this->assertDatabaseCount('devices', 0);
    }

    /**
     * Display and filter devices that use a custom type.
     *
     * Expect: The table shows the custom label and its type filter selects only matching devices.
     */
    public function test_custom_types_are_displayed_and_filterable(): void
    {
        $this->actingAs(User::factory()->withoutTwoFactor()->create());
        $value = app(DeviceTypeService::class)->create('NAS');
        $storage = Device::factory()->create(['type' => $value]);
        $router = Device::factory()->create(['type' => 'router']);
        Livewire::test(ListDevices::class)
            ->assertTableColumnFormattedStateSet('type', 'NAS', record: $storage)
            ->filterTable('type', $value)
            ->assertCanSeeTableRecords([$storage])->assertCanNotSeeTableRecords([$router]);
    }

    /**
     * Resolve built-in and custom labels while switching the application locale.
     *
     * Expect: Built-in labels follow the current locale while custom names remain unchanged and available after device deletion.
     */
    public function test_custom_names_remain_stable_across_locales_and_device_deletion(): void
    {
        $service = app(DeviceTypeService::class);
        $value = $service->create('NAS');
        $device = Device::factory()->create(['type' => $value]);
        $device->delete();
        foreach (['en', 'de'] as $locale) {
            app()->setLocale($locale);
            $this->assertSame(__('devices.types.other'), $service->options()['other']);
            $this->assertSame('NAS', $service->label($value));
            $this->assertSame('NAS', $service->options()[$value]);
        }
        $this->assertDatabaseHas('device_types', ['value' => $value, 'name' => 'NAS']);
    }

    /**
     * Save a raw custom type through the same model path used by CSV imports.
     *
     * Expect: Its original value is preserved and the type becomes available to other device forms immediately.
     */
    public function test_imported_type_values_are_registered_without_rewriting_devices(): void
    {
        $service = app(DeviceTypeService::class);
        $service->options();
        $device = Device::factory()->create(['type' => 'Legacy appliance']);
        $this->assertSame('Legacy appliance', $device->fresh()->type);
        $this->assertSame('Legacy appliance', $service->options()['Legacy appliance']);
    }

    /**
     * Create and update API devices using custom catalog values.
     *
     * Expect: Registered custom values are accepted while unregistered values still fail validation.
     */
    public function test_api_accepts_registered_custom_type_values(): void
    {
        $user = User::factory()->create();
        $this->withToken($user->createToken('Type test')->plainTextToken);
        $service = app(DeviceTypeService::class);
        $nas = $service->create('NAS');
        $pdu = $service->create('PDU');
        $response = $this->postJson('/api/v1/devices', ['name' => 'API storage', 'type' => $nas])->assertCreated()->assertJsonPath('data.type', $nas);
        $id = $response->json('data.id');
        $this->putJson('/api/v1/devices/'.$id, ['type' => $pdu])->assertOk()->assertJsonPath('data.type', $pdu);
        $this->putJson('/api/v1/devices/'.$id, ['type' => 'unregistered-type'])->assertUnprocessable()->assertJsonValidationErrors('type');
    }

    /**
     * Upgrade a legacy inventory that contains arbitrary custom device type strings.
     *
     * Expect: Migration backfill retains those values and adds them to the shared catalog without changing the devices.
     */
    public function test_migration_backfills_legacy_device_type_values(): void
    {
        $migration = require database_path('migrations/2026_10_22_140000_create_device_types_table.php');
        $migration->down();
        DB::table('devices')->insert(['name' => 'Legacy device', 'type' => 'Legacy NAS', 'created_at' => now(), 'updated_at' => now()]);
        $migration->up();
        $this->assertDatabaseHas('device_types', ['value' => 'Legacy NAS', 'name' => 'Legacy NAS']);
        $this->assertDatabaseHas('devices', ['name' => 'Legacy device', 'type' => 'Legacy NAS']);
        $this->assertDatabaseCount('device_types', 10);
    }
}
