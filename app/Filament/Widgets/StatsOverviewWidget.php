<?php

namespace App\Filament\Widgets;

use App\Models\Device;
use App\Models\IpAddress;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalDevices = Device::count();
        $activeDevices = Device::where('status', 'active')->count();
        $maintenanceDevices = Device::where('status', 'maintenance')->count();

        $totalIpAddresses = IpAddress::count();
        $availableIps = IpAddress::where('status', 'available')->count();
        $assignedIps = IpAddress::where('status', 'assigned')->count();

        return [
            Stat::make(__('common.widgets.stats.total_devices'), $totalDevices)
                ->description(__('common.widgets.stats.all_registered_devices'))
                ->descriptionIcon('heroicon-m-server')
                ->color('primary')
                ->chart([7, 12, 9, 14, 17, 15, $totalDevices]),

            Stat::make(__('common.widgets.stats.active_devices'), $activeDevices)
                ->description($maintenanceDevices.' '.__('common.widgets.stats.in_maintenance'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make(__('common.widgets.stats.total_ip_addresses'), $totalIpAddresses)
                ->description(__('common.widgets.stats.all_ip_addresses'))
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('info')
                ->chart([10, 15, 12, 18, 20, 22, $totalIpAddresses]),

            Stat::make(__('common.widgets.stats.available_ips'), $availableIps)
                ->description($assignedIps.' '.__('common.widgets.stats.assigned'))
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('warning'),
        ];
    }
}
