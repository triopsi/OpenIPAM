<?php

namespace Tests\Feature\Filament;

use App\Filament\Auth\Profile;
use App\Filament\Resources\Devices\DeviceResource;
use App\Filament\Resources\IpAddresses\IpAddressResource;
use App\Filament\Resources\IpAddressGroups\IpAddressGroupResource;
use App\Filament\Resources\Settings\SettingResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class PanelLocalizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Resolve navigation groups after the panel has been built and the locale changes.
     *
     * Expect: The same panel returns English and German group labels using the current locale.
     */
    public function test_navigation_groups_follow_the_current_locale(): void
    {
        $panel = Filament::getPanel('admin');

        foreach (['en' => 'Settings', 'de' => 'Einstellungen'] as $locale => $settings) {
            app()->setLocale($locale);
            $labels = array_map(fn ($group) => $group->getLabel(), $panel->getNavigationGroups());
            $this->assertSame(['IPAM', $settings], array_values($labels));
        }
    }

    /**
     * Build the authenticated profile menu using the current Filament action API.
     *
     * Expect: The profile item is an Action with a localized label and the existing profile URL.
     */
    public function test_profile_menu_uses_a_localized_action(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        foreach (['en' => 'Profile', 'de' => 'Profil'] as $locale => $label) {
            app()->setLocale($locale);
            $action = Filament::getPanel('admin')->getUserMenuItems()['profile'];
            $this->assertInstanceOf(Action::class, $action);
            $this->assertSame($label, $action->getLabel());
            $this->assertSame(Profile::getUrl(), $action->getUrl());
            $this->assertStringContainsString('gravatar.com/avatar/', $action->getIcon()->toHtml());
        }
    }

    /**
     * Render the profile schemas in each supported language.
     *
     * Expect: Profile buttons and two-factor information render in the selected language without German text in English.
     */
    public function test_profile_schemas_render_in_both_languages(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();
        $this->actingAs($user);

        foreach (['en' => 'Save profile', 'de' => 'Profil speichern'] as $locale => $button) {
            app()->setLocale($locale);
            $page = Livewire::test(Profile::class)
                ->assertSuccessful()
                ->assertSee($button)
                ->assertSee(__('profile.two_factor_description'))
                ->assertSee(__('profile.disabled'));

            if ($locale === 'en') {
                $page->assertDontSee('Profil speichern')->assertDontSee('Deaktiviert');
            }
        }
    }

    /**
     * Save the personal-data schema after migrating away from deprecated form registration.
     *
     * Expect: A valid profile update persists the name and language and applies the new locale immediately.
     */
    public function test_profile_schema_can_save_a_language_change(): void
    {
        $user = User::factory()->create(['language' => 'en']);
        $this->actingAs($user);

        Livewire::test(Profile::class)
            ->fillForm(['name' => 'Updated profile', 'language' => 'de'], 'profileForm')
            ->call('updateProfile')
            ->assertHasNoFormErrors()
            ->assertRedirect(Profile::getUrl(parameters: ['tab' => 'account']));

        $this->assertSame('Updated profile', $user->fresh()->name);
        $this->assertSame('de', $user->fresh()->language);
        $this->assertSame('de', app()->getLocale());
    }

    /**
     * Switch the two-factor schema from status information to code verification.
     *
     * Expect: The translated code form accepts a valid code, disables two-factor authentication and clears the code.
     */
    public function test_two_factor_schema_can_verify_and_disable(): void
    {
        Notification::fake();
        app()->setLocale('en');
        $user = User::factory()->create(['email_two_factor_enabled' => true]);
        $this->actingAs($user);

        $page = Livewire::test(Profile::class)
            ->call('requestDisableCode')
            ->assertSet('isVerifying', true)
            ->assertSee(__('profile.enter_code'));

        $page->fillForm(['verification_code' => $user->fresh()->email_two_factor_code], 'twoFactorForm')
            ->call('verifyAndDisable')
            ->assertHasNoFormErrors()
            ->assertSet('isVerifying', false)
            ->assertSee(__('profile.disabled'));

        $this->assertFalse($user->fresh()->email_two_factor_enabled);
        $this->assertNull($user->fresh()->email_two_factor_code);
    }

    /**
     * Discover resources from the domain directories using the existing public routes.
     *
     * Expect: Every resource is registered once and retains its original URL slug.
     */
    public function test_domain_resource_discovery_preserves_urls(): void
    {
        $resources = Filament::getPanel('admin')->getResources();
        $expected = [DeviceResource::class => 'devices', IpAddressResource::class => 'ip-addresses',
            IpAddressGroupResource::class => 'ip-address-groups', UserResource::class => 'users',
            SettingResource::class => 'settings'];

        $this->assertEqualsCanonicalizing(array_keys($expected), array_values($resources));
        foreach ($expected as $resource => $slug) {
            $this->assertSame($slug, $resource::getSlug());
            $this->assertSame(url('/admin/'.$slug), $resource::getUrl('index', panel: 'admin'));
        }
    }
}
