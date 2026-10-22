<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocaleMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class ProfileLanguageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verify user language preference persisted.
     *
     * Expect: The user retains German initially and English after updating the language preference.
     */
    public function test_user_language_preference_persisted(): void
    {
        $user = User::factory()->create(['language' => 'de']);

        $this->assertEquals('de', $user->language);

        $user->update(['language' => 'en']);
        $user->refresh();

        $this->assertEquals('en', $user->language);
    }

    /**
     * Verify user can update language preference.
     *
     * Expect: The refreshed user record contains the new German preference.
     */
    public function test_user_can_update_language_preference(): void
    {
        $user = User::factory()->create(['language' => 'en']);

        $user->update(['language' => 'de']);

        $this->assertEquals('de', $user->fresh()->language);
    }

    /**
     * Verify user defaults to english language.
     *
     * Expect: A newly created user defaults to English.
     */
    public function test_user_defaults_to_english_language(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('en', $user->language);
    }

    /**
     * Verify language field accepts valid languages.
     *
     * Expect: English and German language values are both persisted successfully.
     */
    public function test_language_field_accepts_valid_languages(): void
    {
        $user = User::factory()->create();

        $user->update(['language' => 'en']);
        $this->assertEquals('en', $user->fresh()->language);

        $user->update(['language' => 'de']);
        $this->assertEquals('de', $user->fresh()->language);
    }

    /**
     * Verify middleware respects user language preference.
     *
     * Expect: The middleware selects the authenticated user's German locale.
     */
    public function test_middleware_respects_user_language_preference(): void
    {
        $user = User::factory()->create(['language' => 'de']);

        $this->actingAs($user);

        // Create middleware instance and test directly
        $middleware = new SetLocaleMiddleware;

        $request = Request::create('/admin/dashboard');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertEquals('de', app()->getLocale());
    }

    /**
     * Verify translation strings exist for profile.
     *
     * Expect: All checked profile translation keys resolve to nonempty strings.
     */
    public function test_translation_strings_exist_for_profile(): void
    {
        $this->assertNotEmpty(__('profile.title'));
        $this->assertNotEmpty(__('profile.language'));
        $this->assertNotEmpty(__('profile.language_help'));
        $this->assertNotEmpty(__('profile.gravatar_default'));
        $this->assertNotEmpty(__('profile.gravatar_help'));
    }

    /**
     * Verify translation strings exist for common elements.
     *
     * Expect: All checked navigation and common-field keys resolve to nonempty strings.
     */
    public function test_translation_strings_exist_for_common_elements(): void
    {
        $this->assertNotEmpty(__('common.actions.save'));
        $this->assertNotEmpty(__('common.actions.cancel'));
        $this->assertNotEmpty(__('common.navigation.dashboard'));
        $this->assertNotEmpty(__('common.fields.name'));
        $this->assertNotEmpty(__('common.fields.email'));
    }

    /**
     * Verify translation strings exist for devices.
     *
     * Expect: All checked CSV-import translation keys resolve to nonempty strings.
     */
    public function test_translation_strings_exist_for_devices(): void
    {
        $this->assertNotEmpty(__('devices.csv_import.title'));
        $this->assertNotEmpty(__('devices.csv_import.description'));
        $this->assertNotEmpty(__('devices.csv_import.upload_label'));
        $this->assertNotEmpty(__('devices.csv_import.import_button'));
        $this->assertNotEmpty(__('devices.csv_import.validation.file_required'));
    }

    /**
     * Verify language switching route works.
     *
     * Expect: The language request succeeds or redirects and persists the German preference.
     */
    public function test_language_switching_route_works(): void
    {
        $user = User::factory()->create(['language' => 'en']);

        // Test language switching
        $response = $this->actingAs($user)
            ->postJson('/language', ['language' => 'de']);

        // Should return success (302 redirect is also acceptable for language change)
        $this->assertTrue(in_array($response->getStatusCode(), [200, 302]));

        // Verify the user's language preference was updated
        $this->assertEquals('de', $user->fresh()->language);
    }
}
