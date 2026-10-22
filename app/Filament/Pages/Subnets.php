<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Devices\Schemas\DeviceForm;
use App\Models\Device;
use App\Services\DeviceCreationService;
use App\Services\SubnetInventoryService;
use App\Support\Network\Ipv4Subnet;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Attributes\Url;

/** Explore documented IPv4 occupancy without pre-generating address records. */
class Subnets extends Page
{
    protected string $view = 'filament.pages.subnets';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 4;

    #[Url(except: '')]
    public string $subnet = '';

    #[Url(except: 1)]
    public int $block = 1;

    /** @var array<string, mixed>|null User input is separate from the applied subnet. */
    public ?array $data = [];

    /** Translate navigation after the authenticated user's locale has been applied. */
    public static function getNavigationLabel(): string
    {
        return __('subnets.title');
    }

    /** Place the subnet explorer alongside the existing IPAM resources. */
    public static function getNavigationGroup(): string
    {
        return __('common.navigation.ipam');
    }

    /** Return the translated page heading for both supported locales. */
    public function getTitle(): string
    {
        return __('subnets.title');
    }

    /**
     * Restore a shared subnet URL or open the first documented network.
     *
     * Invalid URL input remains editable in the form, while the applied subnet
     * is cleared so malformed links never cause a server-side rendering error.
     */
    public function mount(): void
    {
        $input = $this->subnet ?: (array_key_first(app(SubnetInventoryService::class)->knownSubnets()) ?? '');
        try {
            $this->subnet = $input !== '' ? Ipv4Subnet::parse($input)->cidr() : '';
        } catch (InvalidArgumentException) {
            $this->subnet = '';
        }
        $this->form->fill(['subnet' => $input]);
    }

    /** Define subnet suggestions and a validated CIDR input for undocumented networks. */
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('known_subnet')
                ->label(__('subnets.known_subnets'))
                ->placeholder(__('subnets.choose_subnet'))
                ->options(fn (): array => app(SubnetInventoryService::class)->knownSubnets())
                ->searchable()
                ->live()
                ->afterStateUpdated(function (?string $state, Set $set): void {
                    if ($state !== null) {
                        $set('subnet', $state);
                    }
                })
                ->dehydrated(false),
            TextInput::make('subnet')
                ->label(__('subnets.cidr'))
                ->placeholder('192.168.1.0/24')
                ->required()
                ->maxLength(18)
                ->rules([
                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        try {
                            Ipv4Subnet::parse((string) $value);
                        } catch (InvalidArgumentException) {
                            $fail(__('subnets.validation.cidr'));
                        }
                    },
                ])
                ->helperText(__('subnets.cidr_help')),
        ])->columns(2)->statePath('data');
    }

    /** Apply a validated network and return to its first address block. */
    public function showSubnet(): void
    {
        $data = $this->form->getState();
        $this->subnet = Ipv4Subnet::parse($data['subnet'])->cidr();
        $this->block = 1;
        $this->data['subnet'] = $this->subnet;
    }

    /** Navigate within the subnet without allowing negative or oversized block offsets. */
    public function goToBlock(int $block): void
    {
        try {
            $subnet = Ipv4Subnet::parse($this->subnet);
            $this->block = max(1, min((int) ceil($subnet->size() / SubnetInventoryService::BLOCK_SIZE), $block));
        } catch (InvalidArgumentException) {
            $this->block = 1;
        }
    }

    /**
     * Offer an assignment modal for free cells, including yet-undocumented hosts.
     *
     * The service validates the submitted address and occupancy again on commit;
     * arguments and a previously rendered green cell are never trusted as proof
     * that an address is still available.
     */
    public function assignAddressAction(): Action
    {
        return Action::make('assignAddress')
            ->label(__('subnets.assign'))
            ->modalHeading(fn (array $arguments): string => __('subnets.assign_heading', ['address' => $arguments['address'] ?? '']))
            ->modalDescription(__('subnets.assign_description'))
            ->modalSubmitActionLabel(__('subnets.assign'))
            ->schema([
                Select::make('device_id')
                    ->label(__('subnets.device'))
                    ->searchable()
                    ->required()
                    ->options(fn (): array => $this->searchDevices())
                    ->helperText(__('subnets.device_help'))
                    ->getSearchResultsUsing(fn (string $search): array => $this->searchDevices($search))
                    ->getOptionLabelUsing(fn ($value): ?string => Device::find($value)?->name)
                    ->exists('devices', 'id')
                    ->createOptionForm(fn (): array => DeviceForm::components(withIpAssignments: false))
                    ->createOptionModalHeading(__('devices.create_modal_title'))
                    ->createOptionUsing(fn (array $data): int => app(DeviceCreationService::class)->create($data)->id)
                    ->createOptionAction(fn (Action $action): Action => $action
                        ->label(__('devices.create_modal_title'))
                        ->modalWidth('5xl')),
                Toggle::make('is_primary')->label(__('subnets.primary'))->default(false),
            ])
            ->action(function (array $data, array $arguments): void {
                try {
                    app(SubnetInventoryService::class)->assign(
                        $this->subnet, (string) ($arguments['address'] ?? ''), (int) $data['device_id'], (bool) $data['is_primary'],
                    );
                } catch (ValidationException $exception) {
                    // Surface service validation beside the modal field, rather than losing it at the page root.
                    throw ValidationException::withMessages([
                        $this->getMountedActionSchema()->getStatePath().'.device_id' => $exception->errors()['device_id'],
                    ]);
                }
                Notification::make()->success()->title(__('subnets.assigned'))->send();
            });
    }

    /**
     * Return at most 50 device names for initial options or a case-insensitive search.
     *
     * Laravel's portable whereLike expression uses ILIKE on PostgreSQL, keeping
     * lowercase searches consistent with SQLite and MariaDB without loading all devices.
     *
     * @return array<int, string> Device IDs mapped to their display names.
     */
    protected function searchDevices(string $search = ''): array
    {
        return Device::query()->whereLike('name', '%'.$search.'%')
            ->orderBy('name')->limit(50)->pluck('name', 'id')->all();
    }

    /**
     * Supply one current snapshot per render, tolerating malformed client or URL state.
     *
     * @return array{inventory: array<string, mixed>|null} Data for the raster template.
     */
    protected function getViewData(): array
    {
        try {
            $inventory = $this->subnet !== '' ? app(SubnetInventoryService::class)->snapshot($this->subnet, $this->block) : null;
        } catch (InvalidArgumentException) {
            $inventory = null;
        }

        return ['inventory' => $inventory];
    }
}
