<x-filament::dropdown placement="bottom-end">
    <x-slot name="trigger">
        <button type="button" class="fi-icon-btn"
            title="{{ __('common.navigation.language') }}"
            aria-label="{{ __('common.navigation.language') }}">
            <x-filament::icon icon="heroicon-o-language" class="fi-icon fi-size-lg" />
        </button>
    </x-slot>

    <x-filament::dropdown.list>
        @foreach (config('languages.supported') as $code => $language)
            <form method="POST" action="{{ route('language.switch') }}">
                @csrf
                <input type="hidden" name="language" value="{{ $code }}">
                <x-filament::dropdown.list.item tag="button" type="submit"
                    :icon="$code === app()->getLocale() ? 'heroicon-o-check' : null"
                    :aria-current="$code === app()->getLocale() ? 'true' : null">
                    {{ $language['native_name'] }}
                </x-filament::dropdown.list.item>
            </form>
        @endforeach
    </x-filament::dropdown.list>
</x-filament::dropdown>
