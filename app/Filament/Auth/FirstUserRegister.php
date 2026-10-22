<?php

namespace App\Filament\Auth;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

/**
 * Create the first account through the browser, then close the installer.
 */
class FirstUserRegister extends Register
{
    /**
     * Redirect visitors to sign-in once setup has been completed.
     */
    public function mount(): void
    {
        parent::mount();

        if (User::query()->exists()) {
            $this->redirect(Filament::getLoginUrl());
        }
    }

    /**
     * Serialize installer submissions across containers sharing the database.
     */
    public function register(): ?RegistrationResponse
    {
        try {
            return Cache::store('database')->lock('openipam:first-user', 30)->block(5, function (): ?RegistrationResponse {
                if (User::query()->exists()) {
                    throw ValidationException::withMessages(['data.email' => __('setup.completed')]);
                }

                return parent::register();
            });
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['data.email' => __('setup.busy')]);
        }
    }

    /**
     * Describe initial setup in the browser tab.
     */
    public function getTitle(): string|Htmlable
    {
        return __('setup.title');
    }

    /**
     * Welcome the operator to a fresh OpenIPAM installation.
     */
    public function getHeading(): string|Htmlable|null
    {
        return __('setup.heading');
    }

    /**
     * Explain that this form is used only for the first account.
     */
    public function getSubheading(): string|Htmlable|null
    {
        return __('setup.description');
    }

    /**
     * Require a suitable password for the first account.
     */
    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->minLength(12)
            ->helperText(__('setup.password_hint'));
    }

    /**
     * Label the installer submission as account creation.
     */
    public function getRegisterFormAction(): Action
    {
        return parent::getRegisterFormAction()->label(__('setup.submit'));
    }

    /**
     * Save the first account with the application's default profile settings.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(#[SensitiveParameter] array $data): Model
    {
        return User::create([
            'name' => $data['name'],
            'email' => strtolower(trim($data['email'])),
            'password' => $data['password'],
            'language' => app()->getLocale(),
            'gravatar_type' => 'mp',
            'email_two_factor_enabled' => false,
        ]);
    }
}
