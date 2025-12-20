<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeviceResource\Pages;
use App\Filament\Resources\DeviceResource\RelationManagers;
use App\Models\Device;
use App\Services\IpAssignmentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * DeviceResource
 *
 * Filament resource that provides the admin UI for managing Device entities.
 *
 * Responsibilities:
 * - Define form schema for creating and editing devices.
 * - Define table schema, filters, and actions for listing devices.
 * - Register relation managers for related models.
 * - Register pages (index, create, edit, etc.) for the resource.
 *
 * Intended for use within the Filament admin panel to centralize device management logic
 * such as validation, display, authorization and navigation configuration.
 *
 * @see \Filament\Resources\Resource
 * @since 1.0.0
 */
class DeviceResource extends Resource
{
    protected static ?string $model = Device::class;

    protected static ?string $navigationIcon = 'heroicon-o-server';

    protected static ?string $navigationLabel = null;

    protected static ?string $navigationGroup = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('devices.navigation_label');
    }

    public static function getNavigationGroup(): string
    {
        return __('common.navigation.ipam');
    }

    public static function getModelLabel(): string
    {
        return __('devices.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('devices.plural_model_label');
    }

    protected static ?int $navigationSort = 1;

    /**
     * Configure and return the form used by the Device resource.
     *
     * Defines the form schema, fields, validation rules, layout, and any form-specific
     * behaviors or callbacks for creating and editing Device records in Filament.
     *
     * @param  \Filament\Forms\Form  $form  The form instance to configure.
     * @return \Filament\Forms\Form The configured form instance.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('devices.sections.device_information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('devices.fields.name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('hostname')
                            ->label(__('devices.fields.hostname'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('mac_address')
                            ->label(__('devices.fields.mac_address'))
                            ->maxLength(255)
                            ->placeholder('00:00:00:00:00:00'),
                    ])->columns(3),

                Forms\Components\Section::make(__('devices.sections.details'))
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label(__('devices.fields.type'))
                            ->options([
                                'server' => __('devices.types.server'),
                                'workstation' => __('devices.types.workstation'),
                                'laptop' => __('devices.types.laptop'),
                                'printer' => __('devices.types.printer'),
                                'switch' => __('devices.types.switch'),
                                'router' => __('devices.types.router'),
                                'firewall' => __('devices.types.firewall'),
                                'access_point' => __('devices.types.access_point'),
                                'other' => __('devices.types.other'),
                            ])
                            ->searchable(),
                        Forms\Components\TextInput::make('location')
                            ->label(__('devices.fields.location'))
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->label(__('devices.fields.status'))
                            ->required()
                            ->options([
                                'active' => __('devices.statuses.active'),
                                'inactive' => __('devices.statuses.inactive'),
                                'maintenance' => __('devices.statuses.maintenance'),
                            ])
                            ->default('active'),
                    ])->columns(3),

                Forms\Components\Section::make(__('devices.sections.description'))
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label(__('devices.fields.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('url')
                            ->label(__('devices.fields.url'))
                            ->url()
                            ->placeholder('https://example.com/dashboard')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->helperText(__('devices.helpers.url')),
                    ]),

                Forms\Components\Section::make(__('devices.sections.ip_addresses'))
                    ->schema([
                        Forms\Components\Select::make('primary_ip_address_id')
                            ->label(__('devices.fields.primary_ip'))
                            ->placeholder(__('devices.placeholders.primary_ip'))
                            ->options(function () {
                                $service = new IpAssignmentService;

                                return $service->prepareIpOptionsForDevice();
                            })
                            ->searchable()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $set('additional_ip_addresses', []);
                                }
                            })
                            ->default(function () {
                                $service = new IpAssignmentService;
                                $nextIp = $service->getNextAvailableIp();

                                return $nextIp?->id;
                            }),

                        Forms\Components\Select::make('additional_ip_addresses')
                            ->label(__('devices.fields.additional_ips'))
                            ->multiple()
                            ->options(function (Forms\Get $get) {
                                $service = new IpAssignmentService;
                                $options = $service->prepareIpOptionsForDevice();

                                // Entferne die bereits als primär ausgewählte IP
                                $primaryId = $get('primary_ip_address_id');
                                if ($primaryId) {
                                    foreach ($options as $groupName => &$groupOptions) {
                                        unset($groupOptions[$primaryId]);
                                        if (empty($groupOptions)) {
                                            unset($options[$groupName]);
                                        }
                                    }
                                }

                                return $options;
                            })
                            ->searchable(),
                    ])
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->collapsible(),
            ]);
    }

    /**
     * Configure and return the Filament table for this resource.
     *
     * This method should define the table schema — including columns, filters,
     * header actions, row actions, bulk actions, sorting, and pagination —
     * by modifying and returning the provided Table instance.
     *
     * @param  \Filament\Tables\Table  $table  The table instance to configure.
     * @return \Filament\Tables\Table The configured table instance.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('devices.table.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hostname')
                    ->label(__('devices.table.hostname'))
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('mac_address')
                    ->label(__('devices.table.mac_address'))
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('devices.table.type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'server' => 'success',
                        'router', 'switch', 'firewall' => 'warning',
                        'workstation', 'laptop' => 'info',
                        default => 'gray',
                    })
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
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('location')
                    ->label(__('devices.table.location'))
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('devices.table.status'))
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
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('ipAddresses.ip_address')
                    ->label(__('devices.table.ip_addresses'))
                    ->badge()
                    ->separator(',')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('url')
                    ->label(__('devices.table.url'))
                    ->limit(30)
                    ->placeholder(__('devices.table.no_url'))
                    ->url(fn ($record) => $record->url)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-link')
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('devices.table.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('devices.table.updated_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('devices.filters.type'))
                    ->options([
                        'server' => __('devices.types.server'),
                        'workstation' => __('devices.types.workstation'),
                        'laptop' => __('devices.types.laptop'),
                        'printer' => __('devices.types.printer'),
                        'switch' => __('devices.types.switch'),
                        'router' => __('devices.types.router'),
                        'firewall' => __('devices.types.firewall'),
                        'access_point' => __('devices.types.access_point'),
                        'other' => __('devices.types.other'),
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('devices.filters.status'))
                    ->options([
                        'active' => __('devices.statuses.active'),
                        'inactive' => __('devices.statuses.inactive'),
                        'maintenance' => __('devices.statuses.maintenance'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('csv_export')
                        ->label(__('devices.bulk_actions.csv_export'))
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function ($records) {
                            $filename = __('devices.bulk_actions.filename_prefix').date('Y-m-d_H-i-s').'.csv';

                            $csvData = [];
                            $csvData[] = [
                                __('devices.csv.headers.name'),
                                __('devices.csv.headers.hostname'),
                                __('devices.csv.headers.mac_address'),
                                __('devices.csv.headers.type'),
                                __('devices.csv.headers.location'),
                                __('devices.csv.headers.status'),
                                __('devices.csv.headers.url'),
                                __('devices.csv.headers.description'),
                                __('devices.csv.headers.ip_addresses'),
                                __('devices.csv.headers.primary_ip'),
                                __('devices.csv.headers.created_at'),
                                __('devices.csv.headers.updated_at'),
                            ];

                            foreach ($records as $device) {
                                $ipAddresses = $device->ipAddresses->pluck('ip_address')->implode('; ');
                                $primaryIp = $device->ipAddresses->where('pivot.is_primary', true)->first();

                                $csvData[] = [
                                    $device->name,
                                    $device->hostname ?? '',
                                    $device->mac_address ?? '',
                                    match ($device->type ?? 'other') {
                                        'server' => __('devices.types.server'),
                                        'workstation' => __('devices.types.workstation'),
                                        'laptop' => __('devices.types.laptop'),
                                        'printer' => __('devices.types.printer'),
                                        'switch' => __('devices.types.switch'),
                                        'router' => __('devices.types.router'),
                                        'firewall' => __('devices.types.firewall'),
                                        'access_point' => __('devices.types.access_point'),
                                        default => __('devices.types.other'),
                                    },
                                    $device->location ?? '',
                                    match ($device->status ?? 'active') {
                                        'active' => __('devices.statuses.active'),
                                        'inactive' => __('devices.statuses.inactive'),
                                        'maintenance' => __('devices.statuses.maintenance'),
                                        default => $device->status,
                                    },
                                    $device->url ?? '',
                                    $device->description ?? '',
                                    $ipAddresses,
                                    $primaryIp?->ip_address ?? '',
                                    $device->created_at?->format('d.m.Y H:i:s') ?? '',
                                    $device->updated_at?->format('d.m.Y H:i:s') ?? '',
                                ];
                            }

                            $output = fopen('php://temp', 'w');

                            // UTF-8 BOM für Excel-Kompatibilität
                            fwrite($output, "\xEF\xBB\xBF");

                            foreach ($csvData as $row) {
                                fputcsv($output, $row, ';');
                            }

                            rewind($output);
                            $csvContent = stream_get_contents($output);
                            fclose($output);

                            return response()->streamDownload(
                                function () use ($csvContent) {
                                    echo $csvContent;
                                },
                                $filename,
                                [
                                    'Content-Type' => 'text/csv; charset=utf-8',
                                    'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                                ]
                            );
                        })
                        ->requiresConfirmation()
                        ->modalHeading(__('devices.bulk_actions.csv_modal_heading'))
                        ->modalDescription(__('devices.bulk_actions.csv_modal_description'))
                        ->modalSubmitActionLabel(__('devices.bulk_actions.csv_export_button'))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Get the resource relations.
     *
     * Returns an array of relation manager class names or relation definitions used by
     * Filament to display and manage related records for this resource.
     *
     * Each element is typically a class-string of a RelationManager or a configured
     * relation descriptor that the resource will register.
     *
     * @return array<int, class-string<\Filament\Resources\RelationManager>>
     */
    public static function getRelations(): array
    {
        return [
            RelationManagers\IpAddressesRelationManager::class,
        ];
    }

    /**
     * Get the pages available for this resource.
     *
     * Returns an associative array mapping page identifiers (e.g. "index", "create", "edit")
     * to their corresponding page handlers (page class names, route definitions, or closures)
     * used by the Filament resource.
     *
     * @return array<string, class-string|\Closure|mixed> Array of page keys to handlers.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDevices::route('/'),
            'create' => Pages\CreateDevice::route('/create'),
            'edit' => Pages\EditDevice::route('/{record}/edit'),
        ];
    }
}
