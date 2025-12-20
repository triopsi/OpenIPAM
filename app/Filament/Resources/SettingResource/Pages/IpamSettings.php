<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use App\Models\IpAddressGroup;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class IpamSettings extends Page
{
    protected static string $resource = SettingResource::class;

    protected static string $view = 'filament.resources.setting-resource.pages.ipam-settings';

    protected static ?string $title = null;

    protected static ?string $navigationLabel = null;

    public function getTitle(): string
    {
        return __('settings.ipam.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.ipam.navigation_label');
    }

    protected static string $routePath = '/ipam';

    public ?array $data = [];

    public function mount(): void
    {
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $this->data = [
            'default_ip_group' => Setting::get('default_ip_group'),
            'auto_assign_primary_ip' => Setting::get('auto_assign_primary_ip', true),
            'reserve_network_broadcast' => Setting::get('reserve_network_broadcast', true),
        ];

        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('settings.ipam.section_title'))
                    ->description(__('settings.ipam.section_description'))
                    ->schema([
                        Forms\Components\Select::make('default_ip_group')
                            ->label(__('settings.ipam.fields.default_ip_group'))
                            ->description(__('settings.ipam.fields.default_ip_group_description'))
                            ->options(function () {
                                $groups = IpAddressGroup::pluck('name', 'id')->toArray();

                                return [null => __('settings.ipam.no_specific_group')] + $groups;
                            })
                            ->searchable()
                            ->nullable(),

                        Forms\Components\Toggle::make('auto_assign_primary_ip')
                            ->label(__('settings.ipam.fields.auto_assign_primary_ip'))
                            ->description(__('settings.ipam.fields.auto_assign_primary_ip_description'))
                            ->default(true),

                        Forms\Components\Toggle::make('reserve_network_broadcast')
                            ->label(__('settings.ipam.fields.reserve_network_broadcast'))
                            ->description(__('settings.ipam.fields.reserve_network_broadcast_description'))
                            ->default(true),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('settings.ipam.actions.save'))
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('default_ip_group', $data['default_ip_group'], 'integer');
        Setting::set('auto_assign_primary_ip', $data['auto_assign_primary_ip'], 'boolean');
        Setting::set('reserve_network_broadcast', $data['reserve_network_broadcast'], 'boolean');

        Notification::make()
            ->title(__('settings.ipam.notifications.saved'))
            ->success()
            ->send();
    }
}
