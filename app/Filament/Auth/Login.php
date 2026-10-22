<?php

namespace App\Filament\Auth;

use App\Models\User;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Direct a fresh installation to its first-account setup form.
 */
class Login extends BaseLogin
{
    /**
     * Show the installer until the first account has been created.
     */
    public function mount(): void
    {
        parent::mount();

        if (! User::query()->exists()) {
            $this->redirect(Filament::getRegistrationUrl());
        }
    }

    /**
     * Hide the registration link after the installation has an account.
     */
    public function getSubheading(): string|Htmlable|null
    {
        return User::query()->exists() ? null : parent::getSubheading();
    }
}
