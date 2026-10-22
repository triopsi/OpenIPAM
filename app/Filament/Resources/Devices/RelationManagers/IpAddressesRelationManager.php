<?php

namespace App\Filament\Resources\Devices\RelationManagers;

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

class IpAddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'ipAddresses';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('devices.relation_managers.ip_addresses.title');
    }

    protected static ?string $recordTitleAttribute = 'ip_address';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('ip_address_id')
                    ->label(__('devices.relation_managers.ip_addresses.fields.ip_address'))
                    ->relationship('ipAddresses', 'ip_address')
                    ->searchable()
                    ->preload()
                    ->required(),
                Toggle::make('is_primary')
                    ->label(__('devices.relation_managers.ip_addresses.fields.is_primary'))
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ip_address')
            ->columns([
                TextColumn::make('ip_address')
                    ->label(__('devices.relation_managers.ip_addresses.table.ip_address'))
                    ->copyable()
                    ->searchable(),
                TextColumn::make('version')
                    ->label(__('devices.relation_managers.ip_addresses.table.version'))
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => "IPv{$state}"),
                TextColumn::make('subnet')
                    ->label(__('devices.relation_managers.ip_addresses.table.subnet'))
                    ->toggleable(),
                IconColumn::make('pivot.is_primary')
                    ->label(__('devices.relation_managers.ip_addresses.table.primary'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('devices.relation_managers.ip_addresses.table.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'assigned' => 'warning',
                        'reserved' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'available' => __('ip_addresses.statuses.available'),
                        'assigned' => __('ip_addresses.statuses.assigned'),
                        'reserved' => __('ip_addresses.statuses.reserved'),
                        default => $state,
                    }),
            ])
            ->filters([
                SelectFilter::make('version')
                    ->label(__('devices.relation_managers.ip_addresses.filters.version'))
                    ->options([
                        4 => 'IPv4',
                        6 => 'IPv6',
                    ]),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label(__('devices.relation_managers.ip_addresses.actions.select_ip')),
                        Toggle::make('is_primary')
                            ->label(__('devices.relation_managers.ip_addresses.fields.is_primary_full'))
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
