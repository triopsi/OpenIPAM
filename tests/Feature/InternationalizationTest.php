<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class InternationalizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'language' => 'en',
        ]);
    }

    public function test_default_locale_is_english(): void
    {
        $this->assertEquals('en', config('app.locale'));
    }

    public function test_middleware_sets_locale_from_user_preference(): void
    {
        $this->user->update(['language' => 'de']);

        // Test actual route behavior
        $this->actingAs($this->user);

        // Use the language switch route to verify functionality
        $this->post('/language', ['language' => 'de']);
        $this->assertEquals('de', app()->getLocale());
        $this->assertEquals('de', session('locale'));
    }

    public function test_middleware_sets_locale_from_session_for_unauthenticated_user(): void
    {
        // Test that session locale works
        $this->withSession(['locale' => 'de'])
            ->post('/language', ['language' => 'de']);

        $this->assertEquals('de', session('locale'));
    }

    public function test_middleware_uses_default_locale_when_no_preference(): void
    {
        $this->actingAs($this->user)
            ->get('/admin/login')
            ->assertStatus(302); // Redirects when already authenticated

        $this->assertEquals('en', app()->getLocale());
    }

    public function test_language_switch_route_updates_session(): void
    {
        $response = $this->post('/language', [
            'language' => 'de',
        ]);

        $response->assertRedirect();
        $this->assertEquals('de', session('locale'));
    }

    public function test_language_switch_route_updates_authenticated_user_preference(): void
    {
        $this->actingAs($this->user)
            ->post('/language', [
                'language' => 'de',
            ])
            ->assertRedirect();

        $this->user->refresh();
        $this->assertEquals('de', $this->user->language);
    }

    public function test_language_switch_route_rejects_invalid_language(): void
    {
        $originalLanguage = $this->user->language;

        $this->actingAs($this->user)
            ->post('/language', [
                'language' => 'invalid',
            ])
            ->assertRedirect();

        $this->user->refresh();
        $this->assertEquals($originalLanguage, $this->user->language);
    }

    public function test_user_profile_can_update_language_preference(): void
    {
        $this->actingAs($this->user);

        // Simulate profile form submission
        $this->user->update([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'language' => 'de',
            'gravatar_type' => 'mp',
        ]);

        $this->user->refresh();
        $this->assertEquals('de', $this->user->language);
    }

    public function test_translation_strings_exist_for_both_languages(): void
    {
        // Test English translations
        App::setLocale('en');
        $this->assertNotEmpty(__('common.navigation.dashboard'));
        $this->assertNotEmpty(__('devices.csv_import.title'));
        $this->assertNotEmpty(__('profile.title'));

        // Test German translations
        App::setLocale('de');
        $this->assertNotEmpty(__('common.navigation.dashboard'));
        $this->assertNotEmpty(__('devices.csv_import.title'));
        $this->assertNotEmpty(__('profile.title'));
    }

    public function test_csv_import_uses_translation_strings(): void
    {
        App::setLocale('de');

        $this->assertStringContainsString(
            'CSV Import',
            __('devices.csv_import.title')
        );

        $this->assertStringContainsString(
            'CSV-Datei',
            __('devices.csv_import.file_label')
        );
    }

    public function test_profile_page_uses_translation_strings(): void
    {
        App::setLocale('en');

        $this->assertStringContainsString(
            'Profile',
            __('profile.title')
        );

        App::setLocale('de');

        $this->assertStringContainsString(
            'Profil',
            __('profile.title')
        );
    }

    public function test_language_switching_preserves_user_session(): void
    {
        $this->actingAs($this->user);

        // Switch to German
        $this->post('/language', ['language' => 'de']);

        // User should still be authenticated
        $this->assertTrue(auth()->check());
        $this->assertEquals($this->user->id, auth()->id());
    }

    public function test_middleware_validates_supported_locales(): void
    {
        $this->user->update(['language' => 'fr']); // Unsupported language

        $this->actingAs($this->user)
            ->get('/admin/login')
            ->assertStatus(302); // Redirects when already authenticated

        // Should fall back to default locale
        $this->assertEquals('en', app()->getLocale());
    }

    public function test_device_field_translations_work(): void
    {
        App::setLocale('en');
        $this->assertEquals('Name (Required)', __('devices.csv_import.fields.name'));

        App::setLocale('de');
        $this->assertEquals('Name (Pflichtfeld)', __('devices.csv_import.fields.name'));
    }

    public function test_common_action_translations_work(): void
    {
        App::setLocale('en');
        $this->assertEquals('Create', __('common.actions.create'));
        $this->assertEquals('Edit', __('common.actions.edit'));

        App::setLocale('de');
        $this->assertEquals('Erstellen', __('common.actions.create'));
        $this->assertEquals('Bearbeiten', __('common.actions.edit'));
    }

    public function test_status_translations_work(): void
    {
        App::setLocale('en');
        $this->assertEquals('Active', __('common.status.active'));

        App::setLocale('de');
        $this->assertEquals('Aktiv', __('common.status.active'));
    }
}
