<x-filament-panels::page.simple>
    <form wire:submit="verify" class="space-y-6">
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
    </form>
</x-filament-panels::page.simple>
