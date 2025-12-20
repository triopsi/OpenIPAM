<?php

namespace App\Filament\Resources\IpAddressResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('device_id')
                    ->label(__('ip_addresses.relation_managers.devices.fields.device'))
                    ->relationship('devices', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Toggle::make('is_primary')
                    ->label(__('ip_addresses.relation_managers.devices.fields.is_primary'))
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('ip_addresses.relation_managers.devices.table.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hostname')
                    ->label(__('ip_addresses.relation_managers.devices.table.hostname'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
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
                Tables\Columns\IconColumn::make('pivot.is_primary')
                    ->label(__('ip_addresses.relation_managers.devices.table.primary_ip'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
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
                Tables\Filters\SelectFilter::make('type')
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
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label(__('ip_addresses.relation_managers.devices.actions.select_device')),
                        Forms\Components\Toggle::make('is_primary')
                            ->label(__('ip_addresses.relation_managers.devices.fields.is_primary'))
                            ->default(false),
                    ]),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
