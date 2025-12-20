<?php

namespace App\Filament\Resources\DeviceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('ip_address_id')
                    ->label(__('devices.relation_managers.ip_addresses.fields.ip_address'))
                    ->relationship('ipAddresses', 'ip_address')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Toggle::make('is_primary')
                    ->label(__('devices.relation_managers.ip_addresses.fields.is_primary'))
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ip_address')
            ->columns([
                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('devices.relation_managers.ip_addresses.table.ip_address'))
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('version')
                    ->label(__('devices.relation_managers.ip_addresses.table.version'))
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => "IPv{$state}"),
                Tables\Columns\TextColumn::make('subnet')
                    ->label(__('devices.relation_managers.ip_addresses.table.subnet'))
                    ->toggleable(),
                Tables\Columns\IconColumn::make('pivot.is_primary')
                    ->label(__('devices.relation_managers.ip_addresses.table.primary'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
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
                Tables\Filters\SelectFilter::make('version')
                    ->label(__('devices.relation_managers.ip_addresses.filters.version'))
                    ->options([
                        4 => 'IPv4',
                        6 => 'IPv6',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label(__('devices.relation_managers.ip_addresses.actions.select_ip')),
                        Forms\Components\Toggle::make('is_primary')
                            ->label(__('devices.relation_managers.ip_addresses.fields.is_primary_full'))
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
