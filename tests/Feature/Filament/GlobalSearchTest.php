<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Devices\DeviceResource;
use App\Filament\Resources\IpAddresses\IpAddressResource;
use App\Models\Device;
use App\Models\IpAddress;
use App\Models\IpAddressGroup;
use App\Models\User;
use Filament\Enums\GlobalSearchPosition;
use Filament\Facades\Filament;
use Filament\Livewire\GlobalSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

/** Verify the native topbar search across devices and address inventory. */
class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    /** Authenticate a user without a pending two-factor challenge for panel tests. */
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->withoutTwoFactor()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    /**
     * Render the native search field in the authenticated panel header.
     *
     * Expect: The topbar contains the search input and supports the same Mac and Windows shortcuts as SplitNest.
     */
    public function test_search_is_available_in_the_topbar(): void
    {
        $panel = Filament::getPanel('admin');
        $this->assertSame(GlobalSearchPosition::Topbar, $panel->getGlobalSearchPosition());
        $this->assertSame(['command+k', 'ctrl+k'], $panel->getGlobalSearchKeyBindings());
        $this->get('/admin')->assertOk()->assertSee('type="search"', false);
    }

    /**
     * Search device identifiers and related IPv4/IPv6 values with mixed-case input.
     *
     * Expect: Each supported identifier finds only the matching device and links directly to its editor.
     */
    public function test_devices_are_found_by_identifiers_and_assigned_addresses(): void
    {
        $device = Device::factory()->create([
            'name' => 'Storage NAS', 'hostname' => 'nas.lab.example',
            'mac_address' => 'AA:BB:CC:DD:EE:01', 'location' => 'Lab rack',
        ]);
        Device::factory()->create(['name' => 'Unrelated device', 'hostname' => 'other.example', 'location' => 'Office']);
        foreach (['192.0.2.42', '2001:db8::42'] as $address) {
            $ip = IpAddress::factory()->create(['ip_address' => $address]);
            $device->ipAddresses()->attach($ip);
        }

        foreach (['storage nas', 'NAS.LAB', 'aa:bb:cc:dd:ee:01', 'lab rack', '192.0.2.42', '2001:DB8::42'] as $term) {
            $result = DeviceResource::getGlobalSearchResults($term)->sole();
            $this->assertSame('Storage NAS', $result->title);
            $this->assertSame(DeviceResource::getUrl('edit', ['record' => $device]), $result->url);
            $this->assertEqualsCanonicalizing(['192.0.2.42', '2001:db8::42'], explode(', ', $result->details[__('ip_addresses.plural_model_label')]));
        }
    }

    /**
     * Find address records through network metadata and linked devices.
     *
     * Expect: Partial addresses, subnets, notes, groups, device names and hostnames return the matching IP editor link.
     */
    public function test_addresses_are_found_by_network_context_and_linked_devices(): void
    {
        $group = IpAddressGroup::factory()->create(['name' => 'Homelab servers']);
        $ip = IpAddress::factory()->create([
            'ip_address' => '192.0.2.42', 'subnet' => '192.0.2.0/24',
            'description' => 'Backup endpoint', 'group_id' => $group->id,
        ]);
        $device = Device::factory()->create(['name' => 'Storage NAS', 'hostname' => 'nas.lab.example']);
        $device->ipAddresses()->attach($ip);
        IpAddress::factory()->create(['ip_address' => '198.51.100.10', 'subnet' => '198.51.100.0/24', 'description' => 'Unrelated']);

        foreach (['192.0.2.', '192.0.2.0/24', 'BACKUP', 'homelab servers', 'storage nas', 'NAS.LAB'] as $term) {
            $result = IpAddressResource::getGlobalSearchResults($term)->sole();
            $this->assertSame('192.0.2.42', $result->title);
            $this->assertSame(IpAddressResource::getUrl('edit', ['record' => $ip]), $result->url);
            $this->assertSame('Storage NAS', $result->details[__('devices.plural_model_label')]);
        }
    }

    /**
     * Search from the Livewire field and render localized result categories.
     *
     * Expect: Both resource categories and their links appear in English and German, while unmatched searches show the native empty state.
     */
    public function test_live_search_groups_results_and_handles_empty_searches_in_both_languages(): void
    {
        $device = Device::factory()->create(['name' => 'Storage NAS', 'hostname' => 'nas.lab.example']);
        $ip = IpAddress::factory()->create(['ip_address' => '192.0.2.42']);
        $device->ipAddresses()->attach($ip);

        foreach (['en', 'de'] as $locale) {
            app()->setLocale($locale);
            $page = Livewire::test(GlobalSearch::class)->set('search', 'NAS.LAB')
                ->assertSee(__('devices.plural_model_label'))
                ->assertSee(__('ip_addresses.plural_model_label'))
                ->assertSee('Storage NAS')->assertSee('192.0.2.42')
                ->assertSeeHtml(DeviceResource::getUrl('edit', ['record' => $device]))
                ->assertSeeHtml(IpAddressResource::getUrl('edit', ['record' => $ip]));
            $this->assertCount(2, $page->instance()->getResults()->getCategories());
            if ($locale === 'de') {
                $page->assertSee('2 Ergebnisse angezeigt.')
                    ->assertSee('Pfeiltaste nach unten')
                    ->assertDontSee('Press Down arrow');
            }
            $page->set('search', 'no-such-inventory-record')
                ->assertSee(__('filament-panels::global-search.no_results_message'));
            $page->set('search', '   ');
            $this->assertNull($page->instance()->getResults());
        }
    }

    /**
     * Search a broad term that matches more records than the dropdown can display.
     *
     * Expect: Each resource returns at most twenty results to keep the header dropdown bounded.
     */
    public function test_each_search_category_has_a_bounded_result_count(): void
    {
        Device::factory()->count(21)->create(['name' => 'Limit test device']);
        IpAddress::factory()->count(21)->create(['description' => 'Limit test address']);
        $this->assertCount(20, DeviceResource::getGlobalSearchResults('Limit test device'));
        $this->assertCount(20, IpAddressResource::getGlobalSearchResults('Limit test address'));
    }

    /**
     * Deny resource access using the application's authorization layer.
     *
     * Expect: The native provider omits both resource categories when the user cannot view inventory.
     */
    public function test_search_respects_resource_access_checks(): void
    {
        Device::factory()->create(['name' => 'Private inventory']);
        IpAddress::factory()->create(['description' => 'Private inventory']);
        Gate::before(fn (User $user, string $ability): ?bool => $ability === 'viewAny' ? false : null);

        $this->assertTrue(Filament::getGlobalSearchProvider()->getResults('Private inventory')->getCategories()->isEmpty());
    }
}
