<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // PostgreSQL requires a JSON column for Filament's data->format filter.
        // Cast existing payloads explicitly so upgrades preserve notifications.
        DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE JSONB USING data::jsonb');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE TEXT USING data::text');
    }
};
