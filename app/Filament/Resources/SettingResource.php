<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = null;

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('key')
                    ->label(__('settings.fields.key'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\TextInput::make('label')
                    ->label(__('settings.fields.label'))
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->label(__('settings.fields.description'))
                    ->rows(2),

                Forms\Components\Select::make('group')
                    ->label(__('settings.fields.group'))
                    ->options([
                        'general' => 'Allgemein',
                        'ipam' => 'IPAM',
                        'devices' => 'Geräte',
                        'notifications' => 'Benachrichtigungen',
                    ])
                    ->default('general'),

                Forms\Components\Select::make('type')
                    ->label(__('settings.fields.type'))
                    ->options([
                        'string' => __('settings.types.string'),
                        'boolean' => __('settings.types.boolean'),
                        'integer' => __('settings.types.integer'),
                        'json' => __('settings.types.json'),
                    ])
                    ->default('string')
                    ->live(),

                Forms\Components\TextInput::make('value')
                    ->label(__('settings.fields.value'))
                    ->visible(fn (Forms\Get $get) => $get('type') === 'string'),

                Forms\Components\Toggle::make('value')
                    ->label(__('settings.fields.value'))
                    ->visible(fn (Forms\Get $get) => $get('type') === 'boolean'),

                Forms\Components\TextInput::make('value')
                    ->label(__('settings.fields.value'))
                    ->numeric()
                    ->visible(fn (Forms\Get $get) => $get('type') === 'integer'),

                Forms\Components\Textarea::make('value')
                    ->label(__('settings.fields.value_json'))
                    ->rows(3)
                    ->visible(fn (Forms\Get $get) => $get('type') === 'json'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label(__('settings.table.label'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('key')
                    ->label(__('settings.table.key'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('group')
                    ->label(__('settings.table.group'))
                    ->badge()
                    ->colors([
                        'primary' => 'general',
                        'success' => 'ipam',
                        'warning' => 'devices',
                        'info' => 'notifications',
                    ]),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('settings.table.type'))
                    ->badge(),

                Tables\Columns\TextColumn::make('value')
                    ->label(__('settings.table.value'))
                    ->limit(50),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('settings.table.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->label(__('settings.filters.group'))
                    ->options([
                        'general' => __('settings.groups.general'),
                        'ipam' => __('settings.groups.ipam'),
                        'devices' => __('settings.groups.devices'),
                        'notifications' => __('settings.groups.notifications'),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
            'ipam-settings' => Pages\IpamSettings::route('/ipam'),
        ];
    }
}
