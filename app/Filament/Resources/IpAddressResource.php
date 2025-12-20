<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IpAddressResource\Pages;
use App\Filament\Resources\IpAddressResource\RelationManagers;
use App\Models\IpAddress;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Resource for managing IP address records within the Filament admin panel.
 *
 * This Filament Resource configures the UI and behavior for the underlying IpAddress model,
 * including form schemas for create/edit, table columns and filters for the list view,
 * relation managers for linked models, authorization/policy bindings, and navigation metadata
 * (label, group, icon, and route registration).
 *
 * Responsibilities:
 * - Define form fields and validation rules used when creating or editing IP address entries.
 * - Configure table layout, searchable/sortable columns, filters, bulk actions, and row actions.
 * - Register relation managers to expose related resources (if applicable).
 * - Apply access control to restrict actions based on application policies or roles.
 * - Provide labels and navigation configuration so the resource appears correctly in the admin UI.
 *
 * Intended usage:
 * - Include this resource in the Filament resources map to enable CRUD operations for IP addresses
 *   in the administrative dashboard.
 */
class IpAddressResource extends Resource
{
    protected static ?string $model = IpAddress::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = null;

    protected static ?string $navigationGroup = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('ip_addresses.navigation_label');
    }

    public static function getNavigationGroup(): string
    {
        return __('common.navigation.ipam');
    }

    public static function getModelLabel(): string
    {
        return __('ip_addresses.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ip_addresses.plural_model_label');
    }

    protected static ?int $navigationSort = 2;

    /**
     * Configure and return the Filament form used by the IpAddress resource.
     *
     * Build and return a Form instance that defines the form fields, validation
     * rules, layout, and any default values or reactive behavior for creating
     * and editing IP address records.
     *
     * @param  \Filament\Forms\Form  $form  The form instance to configure.
     * @return \Filament\Forms\Form The configured form instance.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('ip_addresses.sections.ip_information'))
                    ->schema([
                        Forms\Components\Select::make('group_id')
                            ->label(__('ip_addresses.fields.group'))
                            ->relationship('group', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->columnSpanFull(),
                        Forms\Components\Radio::make('generate_mode')
                            ->label(__('ip_addresses.fields.generate_mode'))
                            ->options([
                                'single' => __('ip_addresses.generate_modes.single'),
                                'subnet' => __('ip_addresses.generate_modes.subnet'),
                            ])
                            ->default('single')
                            ->live(),
                        Forms\Components\TextInput::make('ip_address')
                            ->label(__('ip_addresses.fields.ip_address'))
                            ->requiredWithout('subnet_input')
                            ->maxLength(255)
                            ->placeholder('192.168.1.1')
                            ->visible(fn ($get) => $get('generate_mode') === 'single'),
                        Forms\Components\TextInput::make('subnet_input')
                            ->label(__('ip_addresses.fields.subnet_input'))
                            ->requiredWithout('ip_address')
                            ->placeholder('192.168.1.0/24')
                            ->visible(fn ($get) => $get('generate_mode') === 'subnet'),
                        Forms\Components\TextInput::make('subnet_start')
                            ->label(__('ip_addresses.fields.subnet_start'))
                            ->numeric()
                            ->minValue(1)
                            ->placeholder(__('ip_addresses.placeholders.subnet_start'))
                            ->helperText(__('ip_addresses.helpers.subnet_start'))
                            ->visible(fn ($get) => $get('generate_mode') === 'subnet'),
                        Forms\Components\TextInput::make('subnet_count')
                            ->label(__('ip_addresses.fields.subnet_count'))
                            ->numeric()
                            ->minValue(1)
                            ->placeholder(__('ip_addresses.placeholders.subnet_count'))
                            ->helperText(__('ip_addresses.helpers.subnet_count'))
                            ->visible(fn ($get) => $get('generate_mode') === 'subnet'),
                        Forms\Components\Select::make('version')
                            ->label(__('ip_addresses.fields.version'))
                            ->required()
                            ->options([
                                4 => 'IPv4',
                                6 => 'IPv6',
                            ])
                            ->default(4),
                        Forms\Components\Select::make('status')
                            ->label(__('ip_addresses.fields.status'))
                            ->required()
                            ->options([
                                'available' => __('ip_addresses.statuses.available'),
                                'assigned' => __('ip_addresses.statuses.assigned'),
                                'reserved' => __('ip_addresses.statuses.reserved'),
                            ])
                            ->default('available')
                            ->helperText(__('ip_addresses.helpers.status')),
                    ])->columns(3),

                Forms\Components\Section::make(__('ip_addresses.sections.network_config'))
                    ->schema([
                        Forms\Components\TextInput::make('subnet')
                            ->label(__('ip_addresses.fields.subnet'))
                            ->maxLength(255)
                            ->placeholder('255.255.255.0 or /24'),
                        Forms\Components\TextInput::make('gateway')
                            ->label(__('ip_addresses.fields.gateway'))
                            ->maxLength(255)
                            ->placeholder('192.168.1.1'),
                    ])->columns(2),

                Forms\Components\Section::make(__('ip_addresses.sections.description'))
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label(__('ip_addresses.fields.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Configure the table used to display IpAddress resources in the Filament admin.
     *
     * Define columns, filters, sorting, pagination, row actions and bulk actions here.
     * This method receives a Table builder, applies the resource-specific configuration,
     * and returns the configured Table instance for rendering the resource index.
     *
     * @param  \Filament\Tables\Table  $table  The table builder to configure.
     * @return \Filament\Tables\Table The configured table instance.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('ip_addresses.table.ip_address'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage(__('ip_addresses.table.ip_copied'))
                    ->copyMessageDuration(1500),
                Tables\Columns\TextColumn::make('version')
                    ->label(__('ip_addresses.table.version'))
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        4 => 'success',
                        6 => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (int $state): string => "IPv{$state}")
                    ->sortable(),
                Tables\Columns\TextColumn::make('group.name')
                    ->label(__('ip_addresses.table.group'))
                    ->badge()
                    ->color('primary')
                    ->placeholder(__('ip_addresses.table.no_group'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subnet')
                    ->label(__('ip_addresses.table.subnet'))
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('gateway')
                    ->label(__('ip_addresses.table.gateway'))
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('ip_addresses.table.status'))
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
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('devices.name')
                    ->label(__('ip_addresses.table.assigned_devices'))
                    ->badge()
                    ->separator(',')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('ip_addresses.table.description'))
                    ->limit(50)
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('ip_addresses.table.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('ip_addresses.table.updated_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group_id')
                    ->label(__('ip_addresses.filters.group'))
                    ->relationship('group', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('version')
                    ->label(__('ip_addresses.filters.version'))
                    ->options([
                        4 => 'IPv4',
                        6 => 'IPv6',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('ip_addresses.filters.status'))
                    ->options([
                        'available' => __('ip_addresses.statuses.available'),
                        'assigned' => __('ip_addresses.statuses.assigned'),
                        'reserved' => __('ip_addresses.statuses.reserved'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk Edit Gateway
                    Tables\Actions\BulkAction::make('bulk_edit_gateway')
                        ->label(__('ip_addresses.bulk_actions.edit_gateway'))
                        ->icon('heroicon-o-wifi')
                        ->color('warning')
                        ->form([
                            Forms\Components\TextInput::make('gateway')
                                ->label(__('ip_addresses.bulk_actions.new_gateway'))
                                ->required()
                                ->placeholder('192.168.1.1')
                                ->maxLength(255),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'gateway' => $data['gateway'],
                                ]);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    // Bulk Edit IP Group
                    Tables\Actions\BulkAction::make('bulk_edit_group')
                        ->label(__('ip_addresses.bulk_actions.edit_group'))
                        ->icon('heroicon-o-folder')
                        ->color('primary')
                        ->form([
                            Forms\Components\Select::make('group_id')
                                ->label(__('ip_addresses.bulk_actions.new_group'))
                                ->relationship('group', 'name')
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->placeholder(__('ip_addresses.bulk_actions.select_group')),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'group_id' => $data['group_id'],
                                ]);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    // Bulk Edit Subnet
                    Tables\Actions\BulkAction::make('bulk_edit_subnet')
                        ->label(__('ip_addresses.bulk_actions.edit_subnet'))
                        ->icon('heroicon-o-globe-alt')
                        ->color('info')
                        ->form([
                            Forms\Components\TextInput::make('subnet')
                                ->label(__('ip_addresses.bulk_actions.new_subnet'))
                                ->required()
                                ->placeholder('255.255.255.0 oder /24')
                                ->maxLength(255),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'subnet' => $data['subnet'],
                                ]);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    // Bulk Edit Status
                    Tables\Actions\BulkAction::make('bulk_edit_status')
                        ->label(__('ip_addresses.bulk_actions.edit_status'))
                        ->icon('heroicon-o-signal')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label(__('ip_addresses.bulk_actions.new_status'))
                                ->required()
                                ->options([
                                    'available' => __('ip_addresses.statuses.available'),
                                    'assigned' => __('ip_addresses.statuses.assigned'),
                                    'reserved' => __('ip_addresses.statuses.reserved'),
                                ])
                                ->placeholder(__('ip_addresses.bulk_actions.select_status')),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'status' => $data['status'],
                                ]);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    // Bulk Edit Description
                    Tables\Actions\BulkAction::make('bulk_edit_description')
                        ->label(__('ip_addresses.bulk_actions.edit_description'))
                        ->icon('heroicon-o-document-text')
                        ->color('gray')
                        ->form([
                            Forms\Components\Select::make('description_action')
                                ->label(__('ip_addresses.bulk_actions.action'))
                                ->required()
                                ->options([
                                    'replace' => __('ip_addresses.bulk_actions.replace'),
                                    'append' => __('ip_addresses.bulk_actions.append'),
                                    'prepend' => __('ip_addresses.bulk_actions.prepend'),
                                    'clear' => __('ip_addresses.bulk_actions.clear'),
                                ])
                                ->default('replace')
                                ->live(),
                            Forms\Components\Textarea::make('description')
                                ->label(__('ip_addresses.fields.description'))
                                ->rows(3)
                                ->placeholder(__('ip_addresses.bulk_actions.description_placeholder'))
                                ->hidden(fn ($get) => $get('description_action') === 'clear'),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $currentDescription = $record->description ?? '';
                                $newDescription = $data['description'] ?? '';

                                match ($data['description_action']) {
                                    'replace' => $record->update(['description' => $newDescription]),
                                    'append' => $record->update(['description' => $currentDescription.' '.$newDescription]),
                                    'prepend' => $record->update(['description' => $newDescription.' '.$currentDescription]),
                                    'clear' => $record->update(['description' => null]),
                                };
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    // Bulk Advanced Edit
                    Tables\Actions\BulkAction::make('bulk_advanced_edit')
                        ->label(__('ip_addresses.bulk_actions.advanced_edit'))
                        ->icon('heroicon-o-cog-6-tooth')
                        ->color('danger')
                        ->form([
                            Forms\Components\Section::make(__('ip_addresses.bulk_actions.network_settings'))
                                ->schema([
                                    Forms\Components\TextInput::make('gateway')
                                        ->label(__('ip_addresses.bulk_actions.gateway_optional'))
                                        ->placeholder('192.168.1.1')
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('subnet')
                                        ->label(__('ip_addresses.bulk_actions.subnet_optional'))
                                        ->placeholder('255.255.255.0 or /24')
                                        ->maxLength(255),
                                ])->columns(2),

                            Forms\Components\Section::make(__('ip_addresses.bulk_actions.assignment'))
                                ->schema([
                                    Forms\Components\Select::make('group_id')
                                        ->label(__('ip_addresses.bulk_actions.group_optional'))
                                        ->relationship('group', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->nullable()
                                        ->placeholder(__('ip_addresses.bulk_actions.leave_unchanged')),
                                    Forms\Components\Select::make('status')
                                        ->label(__('ip_addresses.bulk_actions.status_optional'))
                                        ->options([
                                            'available' => __('ip_addresses.statuses.available'),
                                            'assigned' => __('ip_addresses.statuses.assigned'),
                                            'reserved' => __('ip_addresses.statuses.reserved'),
                                        ])
                                        ->placeholder(__('ip_addresses.bulk_actions.leave_unchanged')),
                                ])->columns(2),

                            Forms\Components\Section::make(__('ip_addresses.fields.description'))
                                ->schema([
                                    Forms\Components\Textarea::make('description')
                                        ->label(__('ip_addresses.bulk_actions.description_optional'))
                                        ->rows(3)
                                        ->placeholder(__('ip_addresses.bulk_actions.description_no_change')),
                                ]),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $updateData = array_filter([
                                    'gateway' => $data['gateway'] ?? null,
                                    'subnet' => $data['subnet'] ?? null,
                                    'group_id' => $data['group_id'] ?? null,
                                    'status' => $data['status'] ?? null,
                                    'description' => $data['description'] ?? null,
                                ], fn ($value) => $value !== null && $value !== '');

                                if (! empty($updateData)) {
                                    $record->update($updateData);
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Get the resource's relations.
     *
     * Returns an array of relation managers that register the relationships
     * displayed and managed on this Filament resource's pages.
     *
     * @return array<int, class-string<\Filament\Resources\RelationManager>> Relation manager class names
     */
    public static function getRelations(): array
    {
        return [
            RelationManagers\DevicesRelationManager::class,
        ];
    }

    /**
     * Return the pages available for this resource.
     *
     * Provides an associative array mapping page identifiers (e.g. "index", "create", "edit")
     * to their corresponding page classes, route handlers, or configuration definitions
     * used by Filament to register resource routes and pages.
     *
     * @return array<string, mixed> Array mapping page keys to page class names, route handlers, or config arrays.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIpAddresses::route('/'),
            'create' => Pages\CreateIpAddress::route('/create'),
            'edit' => Pages\EditIpAddress::route('/{record}/edit'),
        ];
    }
}
