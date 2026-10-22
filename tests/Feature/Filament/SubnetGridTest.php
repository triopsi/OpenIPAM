<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\Subnets;
use App\Models\Device;
use App\Models\IpAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Cover the authenticated, localized subnet page and its assignment modal. */
class SubnetGridTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Request the subnet explorer without an authenticated session.
     *
     * Expect: Guests are redirected to panel sign-in before viewing network inventory.
     */
    public function test_guests_cannot_access_the_subnet_grid(): void
    {
        $this->get('/admin/subnets')->assertRedirect('/admin/login');
    }

    /**
     * Open an undocumented network using the CIDR input.
     *
     * Expect: Host bits are normalized, all 256 /24 cells are visible and no inventory rows are created.
     */
    public function test_an_empty_network_can_be_explored_without_bulk_creation(): void
    {
        $this->actingAs(User::factory()->withoutTwoFactor()->create());
        $page = Livewire::test(Subnets::class)
            ->assertSee(__('subnets.empty_title'))
            ->fillForm(['subnet' => '192.168.1.73/24'])
            ->call('showSubnet')
            ->assertHasNoFormErrors()
            ->assertSet('subnet', '192.168.1.0/24')
            ->assertSee('192.168.1.0 — Network')
            ->assertSee('192.168.1.255 — Broadcast');

        $this->assertSame(256, substr_count($page->html(), 'wire:key="subnet-cell-'));
        $this->assertDatabaseCount('ip_addresses', 0);
    }

    /**
     * Submit malformed or unsupported networks after opening a valid subnet.
     *
     * Expect: Validation errors preserve the previously applied subnet and malformed shared URLs render safely.
     */
    public function test_invalid_subnet_input_is_rejected_without_losing_the_current_view(): void
    {
        $this->actingAs(User::factory()->withoutTwoFactor()->create());
        foreach (['10.0.0.0/33', '2001:db8::/64', 'not-a-network'] as $input) {
            Livewire::withQueryParams(['subnet' => '10.0.0.0/24'])->test(Subnets::class)
                ->fillForm(['subnet' => $input])->call('showSubnet')
                ->assertHasFormErrors(['subnet'])
                ->assertSet('subnet', '10.0.0.0/24');
        }
        $this->get('/admin/subnets?subnet=invalid')->assertOk()->assertSee(__('subnets.empty_title'));
    }

    /**
     * Navigate through a /16 and attempt an invalid block offset.
     *
     * Expect: Each block contains at most 256 addresses, the next block advances by 256 and invalid offsets are clamped.
     */
    public function test_larger_networks_use_bounded_address_blocks(): void
    {
        $this->actingAs(User::factory()->withoutTwoFactor()->create());
        $page = Livewire::withQueryParams(['subnet' => '10.0.0.0/16'])->test(Subnets::class)
            ->call('goToBlock', 2)->assertSet('block', 2)
            ->assertSee('10.0.1.0 — Free')
            ->assertDontSee('wire:key="subnet-cell-10.0.0.1"', false);
        $this->assertSame(256, substr_count($page->html(), 'wire:key="subnet-cell-'));
        $page->call('goToBlock', -1)->assertSet('block', 1);
        $page->call('goToBlock', PHP_INT_MAX)->assertSet('block', 256);
    }

    /**
     * Assign an undocumented address through the modal from the rendered grid.
     *
     * Expect: The selected device receives the host as its primary IP and the refreshed tile shows its assignment.
     */
    public function test_free_addresses_can_be_assigned_from_the_grid(): void
    {
        $this->actingAs(User::factory()->withoutTwoFactor()->create());
        $device = Device::factory()->create(['name' => 'Lab server']);
        Livewire::withQueryParams(['subnet' => '10.0.0.0/24'])->test(Subnets::class)
            ->callAction('assignAddress', data: ['device_id' => $device->id, 'is_primary' => true], arguments: ['address' => '10.0.0.42'])
            ->assertHasNoActionErrors()
            ->assertSee('10.0.0.42 — Assigned — Lab server');

        $this->assertDatabaseHas('ip_addresses', ['ip_address' => '10.0.0.42', 'subnet' => '10.0.0.0/24', 'status' => 'assigned']);
        $this->assertSame('10.0.0.42', $device->primaryIpAddress()->ip_address);
    }

    /**
     * Search device options with a lowercase query on each configured database engine.
     *
     * Expect: The picker finds matching mixed-case names and excludes unrelated devices.
     */
    public function test_device_search_is_case_insensitive(): void
    {
        $this->actingAs(User::factory()->withoutTwoFactor()->create());
        $device = Device::factory()->create(['name' => 'Lab Server']);
        Device::factory()->create(['name' => 'Router']);
        $page = Livewire::withQueryParams(['subnet' => '10.0.0.0/24'])->test(Subnets::class)
            ->mountAction('assignAddress', ['address' => '10.0.0.42']);
        $field = $page->instance()->getSchema('mountedActionSchema0')->getFlatFields()['device_id'];
        $this->assertSame([$device->id => 'Lab Server'], $field->getSearchResults('lab'));
    }

    /**
     * Submit a stale assignment after another operator reserves the selected host.
     *
     * Expect: The modal reports an occupancy error and leaves the reservation and device relationships intact.
     */
    public function test_stale_grid_assignments_fail_inside_the_modal(): void
    {
        $this->actingAs(User::factory()->withoutTwoFactor()->create());
        $device = Device::factory()->create();
        $page = Livewire::withQueryParams(['subnet' => '10.0.0.0/24'])->test(Subnets::class);
        IpAddress::factory()->create(['ip_address' => '10.0.0.42', 'version' => 4, 'status' => 'reserved']);
        $page->callAction('assignAddress', data: ['device_id' => $device->id], arguments: ['address' => '10.0.0.42'])
            ->assertHasActionErrors(['device_id']);

        $this->assertDatabaseHas('ip_addresses', ['ip_address' => '10.0.0.42', 'status' => 'reserved']);
        $this->assertDatabaseCount('device_ip_address', 0);
    }

    /**
     * Forge action arguments and submit nonexistent devices from the client.
     *
     * Expect: Protected, out-of-subnet and invalid device submissions are rejected without creating address records.
     */
    public function test_forged_action_arguments_cannot_bypass_host_validation(): void
    {
        $this->actingAs(User::factory()->withoutTwoFactor()->create());
        $device = Device::factory()->create();
        foreach (['10.0.0.0', '10.0.0.255', '10.1.0.42'] as $address) {
            Livewire::withQueryParams(['subnet' => '10.0.0.0/24'])->test(Subnets::class)
                ->callAction('assignAddress', data: ['device_id' => $device->id], arguments: ['address' => $address])
                ->assertHasActionErrors(['device_id']);
        }
        Livewire::withQueryParams(['subnet' => '10.0.0.0/24'])->test(Subnets::class)
            ->callAction('assignAddress', data: ['device_id' => 999999], arguments: ['address' => '10.0.0.42'])
            ->assertHasActionErrors(['device_id']);
        $this->assertDatabaseCount('ip_addresses', 0);
    }

    /**
     * Render real grid pages in English and German with existing device data.
     *
     * Expect: Each locale shows translated headings and status labels, escaped device names and links to occupied address records.
     */
    public function test_grid_labels_and_occupied_address_links_are_localized(): void
    {
        $device = Device::factory()->create(['name' => '<script>untrusted</script>']);
        $ip = IpAddress::factory()->create(['ip_address' => '10.0.0.1', 'version' => 4, 'subnet' => '/29', 'status' => 'assigned']);
        $ip->devices()->attach($device);
        foreach (['en' => 'Subnet grid', 'de' => 'Subnetzraster'] as $locale => $title) {
            $this->actingAs(User::factory()->withoutTwoFactor()->create(['language' => $locale]));
            $this->get('/admin/subnets?subnet=10.0.0.0/29')
                ->assertOk()->assertSee($title)
                ->assertSee(__('subnets.statuses.assigned'))
                ->assertSee('/admin/ip-addresses/'.$ip->id.'/edit', false)
                ->assertDontSee('<script>untrusted</script>', false);
        }
    }
}
