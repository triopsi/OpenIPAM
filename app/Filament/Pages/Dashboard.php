<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DeviceIpTreeWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    public static function getNavigationLabel(): string
    {
        return __('common.navigation.dashboard');
    }

    public function getTitle(): string
    {
        return __('common.navigation.dashboard');
    }

    protected static ?int $navigationSort = 0;

    protected static string $routePath = '/';

    /**
     * Get the widgets that should be displayed on the dashboard.
     *
     * @return array<class-string> Array of widget class names
     */
    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            DeviceIpTreeWidget::class,
        ];
    }

    /**
     * Get the number of columns for the dashboard layout.
     *
     * @return int|array Number of columns or responsive column configuration
     */
    public function getColumns(): int|array
    {
        return 1;
    }
}
