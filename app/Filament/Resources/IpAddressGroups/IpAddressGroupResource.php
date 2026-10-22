<?php

namespace App\Filament\Resources\IpAddressGroups;

use App\Filament\Resources\IpAddressGroups\Pages\CreateIpAddressGroup;
use App\Filament\Resources\IpAddressGroups\Pages\EditIpAddressGroup;
use App\Filament\Resources\IpAddressGroups\Pages\ListIpAddressGroups;
use App\Models\IpAddressGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Class IpAddressGroupResource
 *
 * Filament resource providing an administrative interface for managing groups of IP addresses.
 *
 * Responsibilities:
 * - Register form schema and validation logic for creating and editing IP address groups.
 * - Define table columns, filters, sorting, and bulk actions for listing groups.
 * - Configure relations to IP address records and other related resources.
 * - Integrate with Filament navigation, headings, and authorization (policies/permissions).
 *
 * Usage:
 * - Bind to an Eloquent model (e.g. App\Models\IpAddressGroup) via the $model property.
 * - Implement form(), table(), relations(), and getPages() to customize behavior.
 *
 * Notes:
 * - Ensure appropriate policies and permission checks are defined to secure admin access.
 *
 * @see \Filament\Resources\Resource
 * @since 1.0.0
 */
class IpAddressGroupResource extends Resource
{
    protected static ?string $model = IpAddressGroup::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('ip_address_groups.navigation_label');
    }

    public static function getNavigationGroup(): string
    {
        return __('common.navigation.ipam');
    }

    public static function getModelLabel(): string
    {
        return __('ip_address_groups.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('ip_address_groups.plural_model_label');
    }

    protected static ?int $navigationSort = 3;

    /**
     * Build and return the form schema for the IpAddressGroup resource.
     *
     * Configure the Filament form used to create and edit IpAddressGroup records,
     * including fields, layout, validation rules, and state handling.
     *
     * @param  Schema  $schema  The form instance to configure.
     * @return Schema The configured form instance.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('ip_address_groups.fields.name'))
                ->helperText(__('ip_address_groups.fields.name_help'))
                ->required()
                ->columnSpanFull()
                ->maxLength(255),
            Textarea::make('description')
                ->label(__('ip_address_groups.fields.description'))
                ->helperText(__('ip_address_groups.fields.description_help'))
                ->rows(2)
                ->columnSpanFull()
                ->rows(10)
                ->maxLength(500),
        ]);
    }

    /**
     * Configure and return the table definition for the IpAddressGroup resource.
     *
     * This static method should register columns, filters, sorting, pagination,
     * row actions, and bulk actions on the provided Table instance and then
     * return the configured Table for Filament to render.
     *
     * @param  Table  $table  The Table instance to configure.
     * @return Table The configured Table instance.
     */
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')
                ->label(__('ip_address_groups.table.id'))
                ->searchable()
                ->sortable(),
            TextColumn::make('name')
                ->label(__('ip_address_groups.table.name'))
                ->searchable()
                ->sortable(),
            TextColumn::make('description')
                ->label(__('ip_address_groups.table.description'))
                ->limit(50)
                ->toggleable(),
            TextColumn::make('created_at')
                ->label(__('ip_address_groups.table.created_at'))
                ->dateTime('d.m.Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Get the Filament pages for this resource.
     *
     * Returns an associative array mapping page identifiers (for example 'index',
     * 'create', 'edit') to their corresponding Filament page classes or route
     * configurations. Filament uses this configuration to register the resource's
     * pages and routes in the admin panel.
     *
     * @return array<string, class-string|array> Array of page configurations keyed by page name.
     */
    public static function getPages(): array
    {
        return [
            'index' => ListIpAddressGroups::route('/'),
            'create' => CreateIpAddressGroup::route('/create'),
            'edit' => EditIpAddressGroup::route('/{record}/edit'),
        ];
    }
}
