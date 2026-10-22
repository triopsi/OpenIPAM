<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages\CreateSetting;
use App\Filament\Resources\SettingResource\Pages\EditSetting;
use App\Filament\Resources\SettingResource\Pages\IpamSettings;
use App\Filament\Resources\SettingResource\Pages\ListSettings;
use App\Models\Setting;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?string $navigationLabel = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('settings.navigation_label');
    }

    public static function getNavigationGroup(): string
    {
        return __('common.navigation.settings');
    }

    public static function getModelLabel(): string
    {
        return __('settings.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('settings.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label(__('settings.fields.key'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('label')
                    ->label(__('settings.fields.label'))
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->label(__('settings.fields.description'))
                    ->rows(2),

                Select::make('group')
                    ->label(__('settings.fields.group'))
                    ->options([
                        'general' => 'Allgemein',
                        'ipam' => 'IPAM',
                        'devices' => 'Geräte',
                        'notifications' => 'Benachrichtigungen',
                    ])
                    ->default('general'),

                Select::make('type')
                    ->label(__('settings.fields.type'))
                    ->options([
                        'string' => __('settings.types.string'),
                        'boolean' => __('settings.types.boolean'),
                        'integer' => __('settings.types.integer'),
                        'json' => __('settings.types.json'),
                    ])
                    ->default('string')
                    ->live(),

                TextInput::make('value')
                    ->label(__('settings.fields.value'))
                    ->visible(fn (Get $get) => $get('type') === 'string'),

                Toggle::make('value')
                    ->label(__('settings.fields.value'))
                    ->visible(fn (Get $get) => $get('type') === 'boolean'),

                TextInput::make('value')
                    ->label(__('settings.fields.value'))
                    ->numeric()
                    ->visible(fn (Get $get) => $get('type') === 'integer'),

                Textarea::make('value')
                    ->label(__('settings.fields.value_json'))
                    ->rows(3)
                    ->visible(fn (Get $get) => $get('type') === 'json'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label(__('settings.table.label'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('key')
                    ->label(__('settings.table.key'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('group')
                    ->label(__('settings.table.group'))
                    ->badge()
                    ->colors([
                        'primary' => 'general',
                        'success' => 'ipam',
                        'warning' => 'devices',
                        'info' => 'notifications',
                    ]),

                TextColumn::make('type')
                    ->label(__('settings.table.type'))
                    ->badge(),

                TextColumn::make('value')
                    ->label(__('settings.table.value'))
                    ->limit(50),

                TextColumn::make('updated_at')
                    ->label(__('settings.table.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->label(__('settings.filters.group'))
                    ->options([
                        'general' => __('settings.groups.general'),
                        'ipam' => __('settings.groups.ipam'),
                        'devices' => __('settings.groups.devices'),
                        'notifications' => __('settings.groups.notifications'),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSettings::route('/'),
            'create' => CreateSetting::route('/create'),
            'edit' => EditSetting::route('/{record}/edit'),
            'ipam-settings' => IpamSettings::route('/ipam'),
        ];
    }
}
