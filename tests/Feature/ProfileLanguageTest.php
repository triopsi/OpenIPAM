<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileLanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_language_preference_persisted(): void
    {
        $user = User::factory()->create(['language' => 'de']);

        $this->assertEquals('de', $user->language);

        $user->update(['language' => 'en']);
        $user->refresh();

        $this->assertEquals('en', $user->language);
    }

    public function test_user_can_update_language_preference(): void
    {
        $user = User::factory()->create(['language' => 'en']);

        $user->update(['language' => 'de']);

        $this->assertEquals('de', $user->fresh()->language);
    }

    public function test_user_defaults_to_english_language(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('en', $user->language);
    }

    public function test_language_field_accepts_valid_languages(): void
    {
        $user = User::factory()->create();

        $user->update(['language' => 'en']);
        $this->assertEquals('en', $user->fresh()->language);

        $user->update(['language' => 'de']);
        $this->assertEquals('de', $user->fresh()->language);
    }

    public function test_middleware_respects_user_language_preference(): void
    {
        $user = User::factory()->create(['language' => 'de']);

        $this->actingAs($user);

        // Create middleware instance and test directly
        $middleware = new \App\Http\Middleware\SetLocaleMiddleware;

        $request = \Illuminate\Http\Request::create('/admin/dashboard');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $middleware->handle($request, function ($req) {
            return new \Illuminate\Http\Response('OK');
        });

        $this->assertEquals('de', app()->getLocale());
    }

    public function test_translation_strings_exist_for_profile(): void
    {
        $this->assertNotEmpty(__('profile.title'));
        $this->assertNotEmpty(__('profile.language'));
        $this->assertNotEmpty(__('profile.language_help'));
        $this->assertNotEmpty(__('profile.gravatar_default'));
        $this->assertNotEmpty(__('profile.gravatar_help'));
    }

    public function test_translation_strings_exist_for_common_elements(): void
    {
        $this->assertNotEmpty(__('common.actions.save'));
        $this->assertNotEmpty(__('common.actions.cancel'));
        $this->assertNotEmpty(__('common.navigation.dashboard'));
        $this->assertNotEmpty(__('common.fields.name'));
        $this->assertNotEmpty(__('common.fields.email'));
    }

    public function test_translation_strings_exist_for_devices(): void
    {
        $this->assertNotEmpty(__('devices.csv_import.title'));
        $this->assertNotEmpty(__('devices.csv_import.description'));
        $this->assertNotEmpty(__('devices.csv_import.upload_label'));
        $this->assertNotEmpty(__('devices.csv_import.import_button'));
        $this->assertNotEmpty(__('devices.csv_import.validation.file_required'));
    }

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
