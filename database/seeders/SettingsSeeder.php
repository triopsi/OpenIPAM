<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'default_ip_group',
                'value' => null,
                'type' => 'integer',
                'group' => 'ipam',
                'label' => 'Standard IP-Adress Gruppe',
                'description' => 'Diese Gruppe wird beim Erstellen neuer Geräte als Standard für die IP-Zuordnung verwendet',
            ],
            [
                'key' => 'auto_assign_primary_ip',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'ipam',
                'label' => 'Automatische primäre IP-Zuweisung',
                'description' => 'Beim Erstellen neuer Geräte wird automatisch die nächste freie IP-Adresse als primär zugewiesen',
            ],
            [
                'key' => 'reserve_network_broadcast',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'ipam',
                'label' => 'Netzwerk- und Broadcast-Adressen reservieren',
                'description' => 'Die erste und letzte IP-Adresse eines Subnetzes werden automatisch als reserviert markiert',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
