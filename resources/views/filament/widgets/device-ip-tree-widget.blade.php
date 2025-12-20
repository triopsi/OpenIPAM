@php
    $statistics = $this->getViewData()['statistics'];
    $groupedData = $this->getViewData()['groupedData'];
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Kompakter Header mit Statistiken --}}
        <div class="flex items-center justify-between">
            <div>
                <x-filament::section.heading>
                    Network Tree
                </x-filament::section.heading>
                <div class="flex items-center gap-3 mt-1 text-sm text-gray-600 dark:text-gray-400">
                    <span>{{ $statistics['totalDevices'] }} {{ __('widgets.device_tree.devices') }}</span>
                    <span>•</span>
                    <span>{{ $statistics['devicesWithIps'] }} {{ __('widgets.device_tree.with_ips') }}</span>
                    <span>•</span>
                    <span>{{ $statistics['usedIpAddresses'] }} {{ __('widgets.device_tree.ips_used') }}</span>
                </div>
            </div>

            <x-filament::button tag="a" :href="route('filament.admin.resources.devices.create')" color="primary" size="sm" icon="heroicon-o-plus">
                {{ __('widgets.device_tree.add_device') }}
            </x-filament::button>
        </div>

        {{-- Tree Structure --}}
        <div class="space-y-1 mt-6" x-data="{
            expanded: @js(array_keys($groupedData)),
            toggleGroup(group) {
                if (this.expanded.includes(group)) {
                    this.expanded = this.expanded.filter(g => g !== group);
                } else {
                    this.expanded.push(group);
                }
            }
        }">
            @forelse ($groupedData as $groupName => $groupInfo)
                {{-- Ordner/Gruppe --}}
                <div class="border-l-2 border-gray-200 dark:border-gray-700 ml-2">
                    <div class="flex items-center gap-2 py-2 px-3 hover:bg-gray-50 dark:hover:bg-white/5 rounded-r-lg transition-colors cursor-pointer"
                        @click="toggleGroup('{{ $groupName }}')"
                        x-bind:class="expanded.includes('{{ $groupName }}') ? 'bg-gray-50 dark:bg-white/5' : ''">

                        {{-- Expand/Collapse Icon --}}
                        <x-filament::icon x-show="expanded.includes('{{ $groupName }}')"
                            icon="heroicon-o-chevron-down" class="w-4 h-4 text-gray-400 transition-transform" />
                        <x-filament::icon x-show="!expanded.includes('{{ $groupName }}')"
                            icon="heroicon-o-chevron-right" class="w-4 h-4 text-gray-400 transition-transform" />

                        {{-- Ordner Icon --}}
                        @php
                            $folderColor = match ($groupInfo['type']) {
                                'ip-group' => 'text-blue-500',
                                'no-ip' => 'text-gray-400',
                                default => 'text-yellow-500',
                            };
                        @endphp
                        <x-filament::icon x-show="expanded.includes('{{ $groupName }}')"
                            icon="heroicon-o-folder-open" class="w-5 h-5 {{ $folderColor }}" />
                        <x-filament::icon x-show="!expanded.includes('{{ $groupName }}')" icon="heroicon-o-folder"
                            class="w-5 h-5 {{ $folderColor }}" />

                        {{-- Ordner Name und Info --}}
                        <div class="flex-1 flex items-center justify-between">
                            <div>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ $groupName }}
                                </span>
                                @if (isset($groupInfo['group']->description))
                                    <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">
                                        {{ $groupInfo['group']->description }}
                                    </span>
                                @endif
                            </div>

                            <x-filament::badge color="gray" size="sm">
                                {{ $groupInfo['count'] }}
                            </x-filament::badge>
                        </div>
                    </div>

                    {{-- Geräte-Liste (aufklappbar) --}}
                    <div x-show="expanded.includes('{{ $groupName }}')"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="ml-6 border-l-2 border-gray-100 dark:border-gray-800 space-y-1">

                        @foreach ($groupInfo['devices'] as $device)
                            <div
                                class="flex items-center gap-3 py-2 px-3 hover:bg-gray-50 dark:hover:bg-white/5 rounded-r-lg transition-colors group">
                                {{-- Connection Line --}}
                                <div class="w-4 h-px bg-gray-200 dark:bg-gray-700"></div>

                                {{-- Device Icon --}}
                                @php
                                    $deviceIcon = match ($device->type ?? 'other') {
                                        'server' => 'heroicon-o-server',
                                        'workstation', 'laptop' => 'heroicon-o-computer-desktop',
                                        'printer' => 'heroicon-o-printer',
                                        'switch', 'router' => 'heroicon-o-server-stack',
                                        'firewall' => 'heroicon-o-shield-check',
                                        'access_point' => 'heroicon-o-wifi',
                                        default => 'heroicon-o-device-phone-mobile',
                                    };
                                    $iconColor = match ($device->status ?? 'active') {
                                        'active' => 'text-success-500',
                                        'inactive' => 'text-gray-400',
                                        'maintenance' => 'text-warning-500',
                                        default => 'text-gray-500',
                                    };
                                @endphp
                                <x-filament::icon :icon="$deviceIcon"
                                    class="w-4 h-4 {{ $iconColor }} flex-shrink-0" />

                                {{-- Device Info --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-medium text-gray-900 dark:text-white text-sm truncate">
                                            {{ $device->name }}
                                        </span>

                                        {{-- URL Link wenn vorhanden --}}
                                        @if ($device->url)
                                            <a href="{{ $device->url }}" target="_blank"
                                                class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300"
                                                onclick="event.stopPropagation();"
                                                title="{{ __('widgets.device_tree.open_dashboard') }}">
                                                <x-filament::icon icon="heroicon-o-link" class="w-3 h-3" />
                                            </a>
                                        @endif

                                        @php
                                            $statusColor = match ($device->status ?? 'active') {
                                                'active' => 'success',
                                                'inactive' => 'gray',
                                                'maintenance' => 'warning',
                                                default => 'gray',
                                            };
                                        @endphp
                                        <x-filament::badge :color="$statusColor" size="xs">
                                            {{ __('common.status.' . strtolower($device->status ?? 'active')) }}
                                        </x-filament::badge>
                                    </div>

                                    {{-- Secondary Info --}}
                                    <div class="flex items-center gap-4 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        @if ($device->hostname)
                                            <span class="font-mono">{{ $device->hostname }}</span>
                                        @endif

                                        @if ($device->location)
                                            <span class="flex items-center gap-1">
                                                <x-filament::icon icon="heroicon-o-map-pin" class="w-3 h-3" />
                                                {{ $device->location }}
                                            </span>
                                        @endif

                                        {{-- URL anzeigen wenn vorhanden --}}
                                        @if ($device->url)
                                            <span
                                                class="flex items-center gap-1 text-primary-600 dark:text-primary-400">
                                                <x-filament::icon icon="heroicon-o-globe-alt" class="w-3 h-3" />
                                                <span class="truncate max-w-24" title="{{ $device->url }}">
                                                    {{ Str::limit(parse_url($device->url, PHP_URL_HOST) ?: $device->url, 20) }}
                                                </span>
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- IP Addresses --}}
                                <div class="flex-shrink-0 text-right">
                                    @if ($device->ipAddresses->isNotEmpty())
                                        @php $primaryIp = $device->ipAddresses->where('pivot.is_primary', true)->first(); @endphp
                                        @if ($primaryIp)
                                            <code
                                                class="text-xs font-mono text-primary-700 dark:text-primary-300 bg-primary-50 dark:bg-primary-900/20 px-2 py-1 rounded">
                                                {{ $primaryIp->ip_address }}
                                            </code>
                                        @else
                                            <div class="text-xs text-gray-500">
                                                {{ $device->ipAddresses->count() }}
                                                {{ __('widgets.device_tree.ip_count', ['count' => $device->ipAddresses->count()]) }}
                                            </div>
                                        @endif

                                        @if ($device->ipAddresses->count() > 1)
                                            <div class="text-xs text-gray-400 mt-1">
                                                {{ __('widgets.device_tree.additional_ips', ['count' => $device->ipAddresses->count() - 1]) }}
                                            </div>
                                        @endif
                                    @else
                                        <span
                                            class="text-xs text-gray-400 italic">{{ __('widgets.device_tree.no_ip') }}</span>
                                    @endif
                                </div>

                                {{-- Quick Actions --}}
                                <div class="flex-shrink-0 flex items-center gap-2">
                                    {{-- URL Button wenn vorhanden --}}
                                    @if ($device->url)
                                        <a href="{{ $device->url }}" target="_blank"
                                            onclick="event.stopPropagation();"
                                            class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 opacity-70 hover:opacity-100 transition-opacity"
                                            title="{{ __('widgets.device_tree.open_dashboard') }}">
                                            <x-filament::icon icon="heroicon-o-globe-alt" class="w-4 h-4" />
                                        </a>
                                    @endif

                                    {{-- Edit Button (immer sichtbar) --}}
                                    <a href="{{ route('filament.admin.resources.devices.edit', $device) }}"
                                        onclick="event.stopPropagation();"
                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 opacity-70 hover:opacity-100 transition-opacity"
                                        title="{{ __('widgets.device_tree.edit_device') }}">
                                        <x-filament::icon icon="heroicon-o-pencil" class="w-4 h-4" />
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="text-center py-12">
                    <x-filament::icon icon="heroicon-o-folder" class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ __('widgets.device_tree.no_device_groups') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('widgets.device_tree.create_first_device_hint') }}
                    </p>
                    <div class="mt-4">
                        <x-filament::button tag="a" :href="route('filament.admin.resources.devices.create')" color="primary" icon="heroicon-o-plus"
                            size="sm">
                            {{ __('widgets.device_tree.create_first_device') }}
                        </x-filament::button>
                    </div>
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
