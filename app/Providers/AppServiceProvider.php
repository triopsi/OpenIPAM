<?php

namespace App\Providers;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Preserve the existing panel behavior across the Filament upgrade.
        Table::configureUsing(fn (Table $table) => $table->deferFilters(false));
        Field::configureUsing(fn (Field $field) => $field->uniqueValidationIgnoresRecordByDefault(false));
        Fieldset::configureUsing(fn (Fieldset $fieldset) => $fieldset->columnSpanFull());
        Grid::configureUsing(fn (Grid $grid) => $grid->columnSpanFull());
        Section::configureUsing(fn (Section $section) => $section->columnSpanFull());
    }
}
