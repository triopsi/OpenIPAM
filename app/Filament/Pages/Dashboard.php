<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DeviceIpTreeWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

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
