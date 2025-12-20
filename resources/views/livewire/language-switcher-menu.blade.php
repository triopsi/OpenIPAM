<div x-data="{
    currentLanguage: '{{ app()->getLocale() }}',
    switchLanguage(lang) {
        fetch('{{ route('language.switch') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ language: lang })
        }).then(() => window.location.reload());
    }
}">
    <x-filament::dropdown placement="bottom-end">
        <x-slot name="trigger">
            <x-filament::button color="gray" variant="ghost" size="sm" icon="heroicon-o-language">
                <x-slot name="icon">
                    <x-heroicon-o-language class="h-5 w-5" />
                </x-slot>
                <span class="sr-only">{{ __('common.navigation.language') }}</span>
            </x-filament::button>
        </x-slot>

        <x-filament::dropdown.list>
            @foreach (config('languages.supported') as $code => $language)
                <x-filament::dropdown.list.item
                    wire:click="$dispatch('language-switched', { language: '{{ $code }}' })"
                    @click="switchLanguage('{{ $code }}')" :active="app()->getLocale() === $code">
                    <div class="flex items-center gap-2">
                        <span class="text-base leading-none">{{ $language['flag_emoji'] }}</span>
                        <span class="truncate">{{ $language['native_name'] }}</span>
                        <x-heroicon-s-check x-show="currentLanguage === '{{ $code }}'"
                            class="ml-auto h-4 w-4" />
                    </div>
                </x-filament::dropdown.list.item>
            @endforeach

            <x-filament::dropdown.list.item disabled>
                <div class="text-xs text-gray-500">
                    {{ __('common.navigation.current_language') }}:
                    <span class="font-medium" x-text="currentLanguage.toUpperCase()"></span>
                </div>
            </x-filament::dropdown.list.item>
        </x-filament::dropdown.list>
    </x-filament::dropdown>
</div>
