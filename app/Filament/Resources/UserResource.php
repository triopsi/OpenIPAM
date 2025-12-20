<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

/**
 * Filament resource for managing application users.
 *
 * Configures how the User model is presented and managed within the Filament
 * admin panel, including form fields, table columns, relations, pages, and
 * navigation settings. Intended to centralize UI and authorization logic
 * for creating, viewing, editing, and deleting users.
 *
 * @see \App\Models\User
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = null;

    protected static ?string $navigationGroup = null;

    protected static ?string $modelLabel = null;

    protected static ?string $pluralModelLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('users.navigation_label');
    }

    public static function getNavigationGroup(): string
    {
        return __('common.navigation.settings');
    }

    public static function getModelLabel(): string
    {
        return __('users.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('users.plural_model_label');
    }

    protected static ?int $navigationSort = 10;

    /**
     * Configure and return the Filament form used by this resource for creating and editing records.
     *
     * This static method should define the form schema (fields, sections, validation, default values,
     * visibility rules, and other form state) that Filament will render for the resource.
     *
     * @param  Form  $form  The base Filament form instance to configure.
     * @return Form The configured Filament form instance ready for rendering.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('users.sections.user_information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('users.fields.name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label(__('users.fields.email'))
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Select::make('gravatar_type')
                            ->label(__('users.fields.gravatar_type'))
                            ->options([
                                'mp' => __('users.gravatar_types.mystery_person'),
                                'identicon' => __('users.gravatar_types.identicon'),
                                'monsterid' => __('users.gravatar_types.monsterid'),
                                'wavatar' => __('users.gravatar_types.wavatar'),
                                'retro' => __('users.gravatar_types.retro'),
                                'robohash' => __('users.gravatar_types.robohash'),
                                'blank' => __('users.gravatar_types.blank'),
                            ])
                            ->default('mp'),
                    ])->columns(2),

                Forms\Components\Section::make(__('users.sections.password'))
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label(__('users.fields.password'))
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->revealable()
                            ->maxLength(255)
                            ->helperText(fn (string $context): string => $context === 'edit'
                                    ? __('users.helpers.password_keep_current')
                                    : __('users.helpers.password_minimum')
                            ),
                    ]),

                Forms\Components\Section::make(__('users.sections.additional_information'))
                    ->schema([
                        Forms\Components\Toggle::make('email_two_factor_enabled')
                            ->label(__('users.fields.two_factor_auth'))
                            ->helperText(__('users.helpers.two_factor_help'))
                            ->visible(fn (string $context): bool => $context === 'edit')
                            ->afterStateUpdated(function ($state, $record) {
                                if ($record && ! $state) {
                                    // Bei Deaktivierung Code und Ablaufzeit löschen
                                    $record->email_two_factor_code = null;
                                    $record->email_two_factor_expires_at = null;
                                    $record->save();
                                }
                            }),
                    ])
                    ->collapsible(),
            ]);
    }

    /**
     * Configure and return the table used by this resource.
     *
     * Define the table's columns, filters, actions, bulk actions, default sorting,
     * pagination, and any other table-related settings for the Filament resource.
     *
     * @param  \Filament\Tables\Table  $table  The table instance to configure.
     * @return \Filament\Tables\Table The configured table instance.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('gravatar_url')
                    ->label(__('users.table.avatar'))
                    ->circular()
                    ->defaultImageUrl(fn ($record) => $record->gravatar_url)
                    ->size(40),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('users.table.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('users.table.email'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage(__('users.table.email_copied')),
                Tables\Columns\IconColumn::make('email_two_factor_enabled')
                    ->label(__('users.table.two_factor'))
                    ->boolean()
                    ->sortable()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->tooltip(fn ($record): ?string => $record->email_two_factor_enabled
                            ? __('users.table.two_factor_enabled')
                            : __('users.table.two_factor_disabled')
                    ),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('users.table.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('users.table.updated_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('two_factor')
                    ->label(__('users.filters.two_factor_only'))
                    ->query(fn (Builder $query): Builder => $query->where('email_two_factor_enabled', true)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Return the relation managers for this resource.
     *
     * Provide an array of relation manager class names or instantiated relation
     * manager objects that define related resource panels (e.g. hasMany, hasOne)
     * shown on this resource's pages.
     *
     * @return array<int, string|object> Array of RelationManager class names or instances.
     */
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Return the Filament resource's page definitions.
     *
     * Provides an associative array mapping page keys (e.g. "index", "create", "edit")
     * to page definitions (page class references, route helpers, or closures). Filament
     * uses this array to register the resource's pages and their routes.
     *
     * @return array<string, mixed> Array of page definitions keyed by page name.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
