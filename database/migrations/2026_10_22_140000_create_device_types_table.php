<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Create the shared catalog and preserve every existing device type value. */
    public function up(): void
    {
        Schema::create('device_types', function (Blueprint $table): void {
            $table->id();
            $table->string('value')->unique();
            $table->string('name');
            $table->timestamps();
        });

        // Keep migration seed data independent of application models and translations.
        $defaults = [
            'server' => 'Server', 'workstation' => 'Workstation', 'laptop' => 'Laptop',
            'printer' => 'Printer', 'switch' => 'Switch', 'router' => 'Router',
            'firewall' => 'Firewall', 'access_point' => 'Access Point', 'other' => 'Other',
        ];
        foreach ($defaults as $value => $name) {
            DB::table('device_types')->insert(['value' => $value, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (DB::table('devices')->whereNotNull('type')->select('type')->distinct()->cursor() as $device) {
            if ($device->type !== '') {
                DB::table('device_types')->insertOrIgnore([
                    'value' => $device->type, 'name' => $device->type, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    /** Remove only the catalog; device type strings remain intact on rollback. */
    public function down(): void
    {
        Schema::dropIfExists('device_types');
    }
};
