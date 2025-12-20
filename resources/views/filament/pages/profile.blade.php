<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="space-y-6">
            <form wire:submit="updateProfile">
                {{ $this->profileForm }}
                
                <div class="mt-6">
                    <x-filament::button type="submit">
                        Profil speichern
                    </x-filament::button>
                </div>
            </form>
        </div>
        
        <div class="space-y-6">
            <form wire:submit="updatePassword">
                {{ $this->passwordForm }}
                
                <div class="mt-6">
                    <x-filament::button type="submit">
                        Passwort ändern
                    </x-filament::button>
                </div>
            </form>
            
            <div>
                {{ $this->twoFactorForm }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
