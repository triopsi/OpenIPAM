<?php

namespace App\Filament\Resources\Devices\Schemas;

use App\Services\DeviceTypeService;
use App\Services\IpAssignmentService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

/** Share device fields between resource dialogs and inline device creation. */
final class DeviceForm
{
    /**
     * Build fresh schema components for each form instance.
     *
     * Inline creation deliberately omits IP assignment fields so a new device
     * does not consume a different free address before the subnet dialog is saved.
     * The resource creation modal retains the existing optional IP selections.
     *
     * @return array<Component> Localized sections for the current context.
     */
    public static function components(bool $withIpAssignments = true): array
    {
        $components = [
            Section::make(__('devices.sections.device_information'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('devices.fields.name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('hostname')
                        ->label(__('devices.fields.hostname'))
                        ->maxLength(255),
                    TextInput::make('mac_address')
                        ->label(__('devices.fields.mac_address'))
                        ->maxLength(255)
                        ->placeholder('00:00:00:00:00:00'),
                ])->columns(3),

            Section::make(__('devices.sections.details'))
                ->schema([
                    Select::make('type')
                        ->label(__('devices.fields.type'))
                        ->options(fn (): array => app(DeviceTypeService::class)->options())
                        ->getOptionLabelUsing(fn (?string $value): string => app(DeviceTypeService::class)->label($value))
                        ->searchable()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label(__('devices.custom_types.name'))
                                ->required()
                                ->maxLength(100),
                        ])
                        ->createOptionModalHeading(__('devices.custom_types.create'))
                        ->createOptionUsing(fn (array $data): string => app(DeviceTypeService::class)->create($data['name']))
                        ->createOptionAction(fn (Action $action) => $action
                            ->label(__('devices.custom_types.create'))
                            ->modalWidth('md')),
                    TextInput::make('location')
                        ->label(__('devices.fields.location'))
                        ->maxLength(255),
                    Select::make('status')
                        ->label(__('devices.fields.status'))
                        ->required()
                        ->options([
                            'active' => __('devices.statuses.active'),
                            'inactive' => __('devices.statuses.inactive'),
                            'maintenance' => __('devices.statuses.maintenance'),
                        ])
                        ->default('active'),
                ])->columns(3),

            Section::make(__('devices.sections.description'))
                ->schema([
                    Textarea::make('description')
                        ->label(__('devices.fields.description'))
                        ->rows(3)
                        ->columnSpanFull(),
                    TextInput::make('url')
                        ->label(__('devices.fields.url'))
                        ->url()
                        ->placeholder('https://example.com/dashboard')
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->helperText(__('devices.helpers.url')),
                ]),

            Section::make(__('devices.sections.ip_addresses'))
                ->schema([
                    Select::make('primary_ip_address_id')
                        ->label(__('devices.fields.primary_ip'))
                        ->placeholder(__('devices.placeholders.primary_ip'))
                        ->options(function () {
                            $service = new IpAssignmentService;

                            return $service->prepareIpOptionsForDevice();
                        })
                        ->searchable()
                        ->nullable()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set) {
                            if ($state) {
                                $set('additional_ip_addresses', []);
                            }
                        })
                        ->default(function () {
                            $service = new IpAssignmentService;
                            $nextIp = $service->getNextAvailableIp();

                            return $nextIp?->id;
                        }),

                    Select::make('additional_ip_addresses')
                        ->label(__('devices.fields.additional_ips'))
                        ->multiple()
                        ->options(function (Get $get) {
                            $service = new IpAssignmentService;
                            $options = $service->prepareIpOptionsForDevice();

                            // Exclude the selected primary IP from additional assignments.
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
        ];

        if (! $withIpAssignments) {
            array_pop($components);
        }

        return $components;
    }
}
