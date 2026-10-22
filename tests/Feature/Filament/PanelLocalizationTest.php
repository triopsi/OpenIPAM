<?php

namespace Tests\Feature\Filament;

use App\Filament\Auth\Profile;
use App\Filament\Pages\Auth\EmailTwoFactorChallenge;
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
use PHPUnit\Framework\Attributes\DataProvider;
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
     * Expect: Valid six-digit codes retain leading zeros, disable two-factor authentication and are cleared after verification.
     */
    #[DataProvider('verificationCodes')]
    public function test_two_factor_schema_can_verify_and_disable(string $code): void
    {
        Notification::fake();
        app()->setLocale('en');
        $user = User::factory()->create(['email_two_factor_enabled' => true]);
        $this->actingAs($user);

        $page = Livewire::test(Profile::class)
            ->call('requestDisableCode')
            ->assertSet('isVerifying', true)
            ->assertSee(__('profile.enter_code'));

        $user->update(['email_two_factor_code' => $code]);
        $page->fillForm(['verification_code' => $user->fresh()->email_two_factor_code], 'twoFactorForm')
            ->call('verifyAndDisable')
            ->assertHasNoFormErrors()
            ->assertSet('isVerifying', false)
            ->assertSee(__('profile.disabled'));

        $this->assertFalse($user->fresh()->email_two_factor_enabled);
        $this->assertNull($user->fresh()->email_two_factor_code);
    }

    /**
     * Verify sign-in codes through the challenge form, including leading-zero values.
     *
     * Expect: Every valid code completes the session challenge and clears the stored code without disabling 2FA.
     */
    #[DataProvider('verificationCodes')]
    public function test_login_challenge_preserves_six_digit_codes(string $code): void
    {
        Notification::fake();
        $user = User::factory()->create(['email_two_factor_code' => $code]);
        $this->actingAs($user)->withSession(['email_2fa_code_sent' => true]);

        Livewire::test(EmailTwoFactorChallenge::class)
            ->fillForm(['code' => $code])
            ->call('verify')->assertHasNoFormErrors()
            ->assertRedirect(Filament::getUrl());

        $this->assertTrue(session('email_2fa_verified'));
        $this->assertNull($user->fresh()->email_two_factor_code);
        $this->assertTrue($user->fresh()->email_two_factor_enabled);
    }

    /**
     * Reject malformed, incorrect and expired codes in both two-factor forms.
     *
     * Expect: Neither path completes verification or clears the stored code when validation or code verification fails.
     */
    public function test_two_factor_forms_reject_invalid_codes(): void
    {
        Notification::fake();
        foreach (['12345', '1234567', '12e345', '12345x', '654321', 'expired'] as $input) {
            foreach ([Profile::class, EmailTwoFactorChallenge::class] as $component) {
                $user = User::factory()->create(['email_two_factor_code' => '012345']);
                $this->actingAs($user)->withSession(['email_2fa_code_sent' => true, 'email_2fa_verified' => false]);
                $page = Livewire::test($component);
                if ($component === Profile::class) {
                    $page->call('requestDisableCode');
                }
                $user->update([
                    'email_two_factor_code' => '012345',
                    'email_two_factor_expires_at' => $input === 'expired' ? now()->subMinute() : now()->addMinutes(10),
                ]);
                $isProfile = $component === Profile::class;
                $field = $isProfile ? 'verification_code' : 'code';
                $page->fillForm([$field => $input === 'expired' ? '012345' : $input], $isProfile ? 'twoFactorForm' : 'form')
                    ->call($isProfile ? 'verifyAndDisable' : 'verify');
                if (in_array($input, ['654321', 'expired'], true)) {
                    $page->assertHasNoFormErrors([], $isProfile ? 'twoFactorForm' : 'form');
                } else {
                    $page->assertHasFormErrors([$field], $isProfile ? 'twoFactorForm' : 'form');
                }
                if ($isProfile) {
                    $page->assertSet('isVerifying', true);
                }
                $this->assertFalse(session('email_2fa_verified'));
                $this->assertTrue($user->fresh()->email_two_factor_enabled);
                $this->assertSame('012345', $user->fresh()->email_two_factor_code);
            }
        }
    }

    /**
     * Supply deterministic codes that cover ordinary values and leading zeros.
     *
     * @return array<string, array{string}> Codes must remain strings throughout form processing.
     */
    public static function verificationCodes(): array
    {
        return ['ordinary' => ['123456'], 'leading zero' => ['012345'], 'all zeros' => ['000000']];
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
