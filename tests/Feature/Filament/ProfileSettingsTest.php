<?php

namespace Tests\Feature\Filament;

use App\Filament\Auth\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Open the native standalone profile from the authenticated panel.
     *
     * Expect: The existing profile URL shows translated category tabs and a dashboard link without a sidebar.
     */
    public function test_profile_uses_a_standalone_layout_with_category_tabs(): void
    {
        $this->actingAs(User::factory()->withoutTwoFactor()->create());

        $this->get('/admin/profile')
            ->assertOk()
            ->assertSee('fi-simple-layout', false)
            ->assertDontSee('class="fi-sidebar', false)
            ->assertSee('role="tab"', false)
            ->assertSee(__('profile.tabs.account'))
            ->assertSee(__('profile.tabs.password'))
            ->assertSee(__('profile.tabs.security'))
            ->assertSee(__('profile.back_to_dashboard'));
    }

    /**
     * Change the password from its dedicated tab using the current password.
     *
     * Expect: A valid submission changes the password, clears its form and keeps the user authenticated.
     */
    public function test_password_tab_can_change_the_password(): void
    {
        $user = User::factory()->withoutTwoFactor()->create(['password' => 'old-password']);
        $this->actingAs($user);

        Livewire::test(Profile::class)
            ->fillForm([
                'current_password' => 'old-password',
                'password' => 'new-long-password',
                'password_confirmation' => 'new-long-password',
            ], 'passwordForm')
            ->call('updatePassword')
            ->assertHasNoFormErrors()
            ->assertSet('passwordData.password', null);

        $this->assertTrue(Hash::check('new-long-password', $user->fresh()->password));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Submit the password tab with an incorrect current password.
     *
     * Expect: Validation rejects the request and preserves the existing password.
     */
    public function test_password_tab_rejects_an_incorrect_current_password(): void
    {
        $user = User::factory()->withoutTwoFactor()->create(['password' => 'old-password']);
        $this->actingAs($user);

        Livewire::test(Profile::class)
            ->fillForm([
                'current_password' => 'incorrect-password',
                'password' => 'new-long-password',
                'password_confirmation' => 'new-long-password',
            ], 'passwordForm')
            ->call('updatePassword')
            ->assertHasFormErrors(['current_password'], form: 'passwordForm');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    /**
     * Save a profile using an email address that belongs to another account.
     *
     * Expect: The account tab rejects the duplicate email and leaves the user's email unchanged.
     */
    public function test_account_tab_rejects_another_users_email(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(Profile::class)
            ->fillForm(['email' => $otherUser->email], 'profileForm')
            ->call('updateProfile')
            ->assertHasFormErrors(['email' => 'unique'], form: 'profileForm');

        $this->assertNotSame($otherUser->email, $user->fresh()->email);
    }

    /**
     * Render the same language dropdown on the dashboard and standalone profile.
     *
     * Expect: Both pages offer native language names through CSRF-protected forms and mark the current selection.
     */
    public function test_language_switcher_is_available_on_both_layouts(): void
    {
        $this->actingAs(User::factory()->withoutTwoFactor()->create(['language' => 'de']));

        foreach (['/admin', '/admin/profile'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('Deutsch')
                ->assertSee('English')
                ->assertSee('name="language" value="de"', false)
                ->assertSee('name="_token"', false)
                ->assertSee('aria-current="true"', false)
                ->assertDontSee('switchLanguage(', false)
                ->assertDontSee('language-switched', false);
        }
    }

    /**
     * Switch language from the password tab and return to the same page.
     *
     * Expect: The selected locale persists to the user and session while the redirect preserves the tab URL.
     */
    public function test_language_switch_returns_to_the_active_profile_tab(): void
    {
        $user = User::factory()->withoutTwoFactor()->create(['language' => 'en']);
        $this->actingAs($user);
        $url = url('/admin/profile?tab=password');

        $this->from($url)->post(route('language.switch'), ['language' => 'de'])
            ->assertRedirect($url)
            ->assertSessionHas('locale', 'de');

        $this->assertSame('de', $user->fresh()->language);
        $this->get('/admin/profile?tab=password')
            ->assertOk()
            ->assertSee('Zurück zur Übersicht')
            ->assertSee('Passwort')
            ->assertSee('activeTab: 2', false);
    }
}
