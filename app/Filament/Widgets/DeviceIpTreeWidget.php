<?php

namespace App\Filament\Widgets;

use App\Models\Device;
use App\Models\IpAddress;
use App\Models\IpAddressGroup;
use Filament\Widgets\Widget;

class DeviceIpTreeWidget extends Widget
{
    protected static string $view = 'filament.widgets.device-ip-tree-widget';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = null;

    protected static ?string $description = null;

    public static function getHeading(): string
    {
        return __('common.widgets.device_ip_tree.heading');
    }

    public function getDescription(): ?string
    {
        return __('common.widgets.device_ip_tree.description');
    }

    public function getViewData(): array
    {
        // Hole alle Geräte mit ihren IP-Adressen und Gruppen
        $devices = Device::with(['ipAddresses.group'])
            ->orderBy('name')
            ->get();

        // Gruppiere nach IP-Adress Gruppen
        $groupedData = [];

        // Geräte ohne IP-Adressen
        $devicesWithoutIps = $devices->filter(function ($device) {
            return $device->ipAddresses->isEmpty();
        });

        if ($devicesWithoutIps->isNotEmpty()) {
            $groupedData[__('common.widgets.device_ip_tree.no_ip_devices')] = [
                'type' => 'no-ip',
                'devices' => $devicesWithoutIps,
                'count' => $devicesWithoutIps->count(),
            ];
        }

        // Geräte mit IP-Adressen, gruppiert nach IP-Gruppen
        $devicesWithIps = $devices->filter(function ($device) {
            return $device->ipAddresses->isNotEmpty();
        });

        $ipGroups = IpAddressGroup::all();

        foreach ($ipGroups as $group) {
            $groupDevices = $devicesWithIps->filter(function ($device) use ($group) {
                return $device->ipAddresses->contains('group_id', $group->id);
            });

            if ($groupDevices->isNotEmpty()) {
                $groupedData[$group->name] = [
                    'type' => 'ip-group',
                    'group' => $group,
                    'devices' => $groupDevices,
                    'count' => $groupDevices->count(),
                ];
            }
        }

        // Geräte mit IP-Adressen ohne Gruppe
        $devicesWithUngroupedIps = $devicesWithIps->filter(function ($device) {
            return $device->ipAddresses->contains('group_id', null);
        });

        if ($devicesWithUngroupedIps->isNotEmpty()) {
            $groupedData[__('common.widgets.device_ip_tree.no_ip_group')] = [
                'type' => 'ungrouped',
                'devices' => $devicesWithUngroupedIps,
                'count' => $devicesWithUngroupedIps->count(),
            ];
        }

        // Statistiken
        $totalDevices = $devices->count();
        $devicesWithIpsCount = $devicesWithIps->count();
        $totalIpAddresses = IpAddress::where('status', 'assigned')->count();
        $availableIpAddresses = IpAddress::where('status', 'available')->count();

        return [
            'groupedData' => $groupedData,
            'statistics' => [
                'totalDevices' => $totalDevices,
                'devicesWithIps' => $devicesWithIpsCount,
                'devicesWithoutIps' => $totalDevices - $devicesWithIpsCount,
                'usedIpAddresses' => $totalIpAddresses,
                'availableIpAddresses' => $availableIpAddresses,
                'totalIpAddresses' => $totalIpAddresses + $availableIpAddresses,
            ],
        ];
    }

    public static function canView(): bool
    {
        return true;
    }
}
