<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Filament\Notifications\Livewire\DatabaseNotifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DatabaseNotificationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Render the notification bell and query JSON payloads on the configured database engine.
     *
     * Expect: Only the current user's unread Filament notifications contribute to the bell count.
     */
    public function test_notification_bell_filters_json_payloads(): void
    {
        $user = User::factory()->withoutTwoFactor()->create();
        $otherUser = User::factory()->withoutTwoFactor()->create();
        $this->actingAs($user);

        foreach ([$user, $otherUser] as $recipient) {
            $recipient->notifications()->create([
                'id' => (string) Str::uuid(), 'type' => 'test',
                'data' => ['format' => 'filament', 'title' => 'Network update'],
            ]);
        }
        $user->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => 'test',
            'data' => ['format' => 'external', 'title' => 'External update'],
        ]);
        $user->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => 'test',
            'data' => ['format' => 'filament', 'title' => 'Already read'], 'read_at' => now(),
        ]);

        $bell = Livewire::test(DatabaseNotifications::class)->assertSuccessful();
        $this->assertSame(1, $bell->instance()->getUnreadNotificationsCount());
    }

    /**
     * Upgrade a legacy PostgreSQL text payload and reverse the column migration.
     *
     * Expect: Both migration directions preserve notification data and the upgraded JSON payload supports Filament queries.
     */
    public function test_postgresql_upgrade_preserves_existing_notifications(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Legacy text-to-JSON conversion is specific to PostgreSQL.');
        }

        $migration = require database_path('migrations/2026_10_22_100000_use_jsonb_for_postgresql_notification_data.php');
        $migration->down();
        $user = User::factory()->create();
        $payload = ['format' => 'filament', 'title' => 'Existing notification', 'body' => 'Überblick'];
        $notification = $user->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => 'test', 'data' => $payload,
        ]);

        $migration->up();
        $this->assertEquals($payload, $notification->fresh()->data);
        $this->assertSame(1, $user->notifications()->where('data->format', 'filament')->count());

        $migration->down();
        $this->assertEquals($payload, $notification->fresh()->data);
        $migration->up();
    }
}
