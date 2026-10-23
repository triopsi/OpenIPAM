<?php

namespace App\Filament\Resources\Devices;

use App\Filament\Resources\Devices\Pages\EditDevice;
use App\Filament\Resources\Devices\Pages\ListDevices;
use App\Filament\Resources\Devices\RelationManagers\IpAddressesRelationManager;
use App\Filament\Resources\Devices\Schemas\DeviceForm;
use App\Models\Device;
use App\Services\DeviceTypeService;
use Closure;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?bool $isGlobalSearchForcedCaseInsensitive = true;

    protected static int $globalSearchResultsLimit = 20;

    /**
     * Search device identifiers and assigned addresses from the panel's topbar.
     *
     * @return array<string> Columns and relationships supported by native global search.
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'hostname', 'mac_address', 'location', 'ipAddresses.ip_address'];
    }

    /** Eager-load address labels to avoid a separate query for each search result. */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with('ipAddresses:id,ip_address');
    }

    /**
     * Show identifying context alongside each device title using current-locale labels.
     *
     * @return array<string, string> Nonempty details, with at most three address labels.
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return array_filter([
            __('devices.fields.hostname') => $record->hostname,
            __('devices.fields.mac_address') => $record->mac_address,
            __('ip_addresses.plural_model_label') => $record->ipAddresses->take(3)->pluck('ip_address')->implode(', '),
        ], fn ($value): bool => filled($value));
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-server';

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = null;

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
     * @param  Schema  $schema  The form instance to configure.
     * @return Schema The configured form instance.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components(DeviceForm::components());
    }

    /**
     * Configure and return the Filament table for this resource.
     *
     * This method should define the table schema — including columns, filters,
     * header actions, row actions, bulk actions, sorting, and pagination —
     * by modifying and returning the provided Table instance.
     *
     * @param  Table  $table  The table instance to configure.
     * @return Table The configured table instance.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('devices.table.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('hostname')
                    ->label(__('devices.table.hostname'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('mac_address')
                    ->label(__('devices.table.mac_address'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->label(__('devices.table.type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'server' => 'success',
                        'router', 'switch', 'firewall' => 'warning',
                        'workstation', 'laptop' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => app(DeviceTypeService::class)->label($state))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('location')
                    ->label(__('devices.table.location'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
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
                TextColumn::make('ipAddresses.ip_address')
                    ->label(__('devices.table.ip_addresses'))
                    ->badge()
                    ->separator(',')
                    ->toggleable(),
                TextColumn::make('url')
                    ->label(__('devices.table.url'))
                    ->limit(30)
                    ->placeholder(__('devices.table.no_url'))
                    ->url(fn ($record) => $record->url)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-link')
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('devices.table.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('devices.table.updated_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('devices.filters.type'))
                    ->options(fn (): array => app(DeviceTypeService::class)->options()),
                SelectFilter::make('status')
                    ->label(__('devices.filters.status'))
                    ->options([
                        'active' => __('devices.statuses.active'),
                        'inactive' => __('devices.statuses.inactive'),
                        'maintenance' => __('devices.statuses.maintenance'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('csv_export')
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
                                    app(DeviceTypeService::class)->label($device->type),
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

                    DeleteBulkAction::make(),
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
     * @return array<int, class-string<RelationManager>>
     */
    public static function getRelations(): array
    {
        return [
            IpAddressesRelationManager::class,
        ];
    }

    /**
     * Get the pages available for this resource.
     *
     * Returns an associative array mapping page identifiers (e.g. "index", "create", "edit")
     * to their corresponding page handlers (page class names, route definitions, or closures)
     * used by the Filament resource.
     *
     * @return array<string, class-string|Closure|mixed> Array of page keys to handlers.
     */
    public static function getPages(): array
    {
        return [
            'index' => ListDevices::route('/'),
            'edit' => EditDevice::route('/{record}/edit'),
        ];
    }
}
