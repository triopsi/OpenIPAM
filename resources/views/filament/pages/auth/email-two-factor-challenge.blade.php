<x-filament-panels::page.simple>
    <x-filament-panels::form wire:submit="verify">
        {{ $this->form }}

        <x-filament::button
            type="submit"
            class="w-full"
        >
            {{ __('auth.two_factor.verify_button') }}
        </x-filament::button>

        <div class="mt-4 text-center">
            <x-filament::link
                wire:click="resendCode"
                tag="button"
                type="button"
            >
                {{ __('auth.two_factor.resend_code') }}
            </x-filament::link>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page.simple>
