<?php

namespace App\Filament\Resources\IpAddressResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DevicesRelationManager extends RelationManager
{
    protected static string $relationship = 'devices';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('ip_addresses.relation_managers.devices.title');
    }

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('device_id')
                    ->label(__('ip_addresses.relation_managers.devices.fields.device'))
                    ->relationship('devices', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Toggle::make('is_primary')
                    ->label(__('ip_addresses.relation_managers.devices.fields.is_primary'))
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('ip_addresses.relation_managers.devices.table.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('hostname')
                    ->label(__('ip_addresses.relation_managers.devices.table.hostname'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('ip_addresses.relation_managers.devices.table.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'server' => __('devices.types.server'),
                        'workstation' => __('devices.types.workstation'),
                        'laptop' => __('devices.types.laptop'),
                        'printer' => __('devices.types.printer'),
                        'switch' => __('devices.types.switch'),
                        'router' => __('devices.types.router'),
                        'firewall' => __('devices.types.firewall'),
                        'access_point' => __('devices.types.access_point'),
                        default => __('devices.types.other'),
                    }),
                IconColumn::make('pivot.is_primary')
                    ->label(__('ip_addresses.relation_managers.devices.table.primary_ip'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('ip_addresses.relation_managers.devices.table.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'maintenance' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => __('devices.statuses.active'),
                        'inactive' => __('devices.statuses.inactive'),
                        'maintenance' => __('devices.statuses.maintenance'),
                        default => $state,
                    }),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('ip_addresses.relation_managers.devices.filters.type'))
                    ->options([
                        'server' => __('devices.types.server'),
                        'workstation' => __('devices.types.workstation'),
                        'laptop' => __('devices.types.laptop'),
                        'printer' => __('devices.types.printer'),
                        'switch' => __('devices.types.switch'),
                        'router' => __('devices.types.router'),
                        'firewall' => __('devices.types.firewall'),
                        'access_point' => __('devices.types.access_point'),
                    ]),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label(__('ip_addresses.relation_managers.devices.actions.select_device')),
                        Toggle::make('is_primary')
                            ->label(__('ip_addresses.relation_managers.devices.fields.is_primary'))
                            ->default(false),
                    ]),
            ])
            ->recordActions([
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
