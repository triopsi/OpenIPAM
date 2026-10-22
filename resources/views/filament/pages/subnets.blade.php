<x-filament-panels::page>
    <x-filament::section>
        <form wire:submit="showSubnet" class="space-y-4">
            {{ $this->form }}
            <x-filament::button type="submit" icon="heroicon-o-squares-2x2">
                {{ __('subnets.open') }}
            </x-filament::button>
        </form>
    </x-filament::section>

    @if ($inventory)
        <x-filament::section>
            <x-slot name="heading">{{ $inventory['cidr'] }}</x-slot>
            <x-slot name="description">{{ __('subnets.inventory_notice') }}</x-slot>

            <dl class="ipam-subnet-stats">
                <div><dt>{{ __('subnets.total') }}</dt><dd>{{ \Illuminate\Support\Number::format($inventory['total'], locale: app()->getLocale()) }}</dd></div>
                @foreach (['available', 'assigned', 'reserved'] as $status)
                    <div><dt>{{ __('subnets.statuses.'.$status) }}</dt><dd>{{ \Illuminate\Support\Number::format($inventory['counts'][$status], locale: app()->getLocale()) }}</dd></div>
                @endforeach
            </dl>
            <p class="ipam-subnet-caption">{{ __('subnets.utilization', ['percent' => \Illuminate\Support\Number::format($inventory['utilization'], precision: 1, locale: app()->getLocale())]) }}</p>

            <ul class="ipam-subnet-legend" aria-label="{{ __('subnets.legend') }}">
                @foreach (['available', 'assigned', 'reserved', 'protected'] as $status)
                    <li><span class="ipam-subnet-dot ipam-subnet-{{ $status }}" aria-hidden="true"></span>{{ __('subnets.statuses.'.$status) }}</li>
                @endforeach
            </ul>

            <p class="ipam-subnet-caption">{{ __('subnets.showing', ['first' => $inventory['first'], 'last' => $inventory['last']]) }}</p>
            <div class="ipam-subnet-grid" role="group" aria-label="{{ __('subnets.grid_label', ['subnet' => $inventory['cidr']]) }}" wire:loading.class="opacity-50">
                @foreach ($inventory['cells'] as $cell)
                    @php
                        $statusLabel = __('subnets.statuses.'.$cell['status']);
                        $details = $cell['kind'] ? __('subnets.kinds.'.$cell['kind']) : $statusLabel;
                        $label = $cell['address'].' — '.$details;
                        if ($cell['devices']) {
                            $label .= ' — '.implode(', ', $cell['devices']);
                        }
                        $tileClass = 'ipam-subnet-cell ipam-subnet-'.($cell['protected'] ? 'protected' : $cell['status']);
                    @endphp
                    @if ($cell['record_id'] && ($cell['status'] !== 'available' || $cell['protected']))
                        <a href="{{ \App\Filament\Resources\IpAddresses\IpAddressResource::getUrl('edit', ['record' => $cell['record_id']]) }}"
                           class="{{ $tileClass }}" title="{{ $label }}" aria-label="{{ $label }}"
                           wire:key="subnet-cell-{{ $cell['address'] }}">
                            <span class="ipam-subnet-address">{{ $cell['suffix'] }}</span>
                            <span class="ipam-subnet-status">{{ $details }}</span>
                        </a>
                    @else
                        <button type="button" class="{{ $tileClass }}" title="{{ $label }}" aria-label="{{ $label }}"
                            @disabled($cell['protected'])
                            @if (! $cell['protected'])
                                wire:click="mountAction('assignAddress', { address: '{{ $cell['address'] }}' })"
                            @endif
                            wire:loading.attr="disabled" wire:key="subnet-cell-{{ $cell['address'] }}">
                            <span class="ipam-subnet-address">{{ $cell['suffix'] }}</span>
                            <span class="ipam-subnet-status">{{ $details }}</span>
                        </button>
                    @endif
                @endforeach
            </div>

            @if ($inventory['blocks'] > 1)
                <nav class="ipam-subnet-pagination" aria-label="{{ __('subnets.pagination') }}">
                    <x-filament::button color="gray" icon="heroicon-o-chevron-left"
                        wire:click="goToBlock({{ $inventory['block'] - 1 }})" :disabled="$inventory['block'] === 1">
                        {{ __('common.actions.previous') }}
                    </x-filament::button>
                    <span>{{ __('subnets.block', ['current' => \Illuminate\Support\Number::format($inventory['block'], locale: app()->getLocale()), 'total' => \Illuminate\Support\Number::format($inventory['blocks'], locale: app()->getLocale())]) }}</span>
                    <x-filament::button color="gray" icon="heroicon-o-chevron-right"
                        wire:click="goToBlock({{ $inventory['block'] + 1 }})" :disabled="$inventory['block'] === $inventory['blocks']">
                        {{ __('common.actions.next') }}
                    </x-filament::button>
                </nav>
            @endif
        </x-filament::section>
    @else
        <x-filament::section>
            <x-slot name="heading">{{ __('subnets.empty_title') }}</x-slot>
            <p>{{ __('subnets.empty_description') }}</p>
        </x-filament::section>
    @endif
</x-filament-panels::page>
