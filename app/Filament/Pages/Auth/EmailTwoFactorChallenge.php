<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Notifications\EmailTwoFactorCode;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Illuminate\Support\Facades\Auth;

class EmailTwoFactorChallenge extends SimplePage
{
    protected static string $view = 'filament.pages.auth.email-two-factor-challenge';

    protected static ?string $title = null;

    /**
     * Get the page title for the two-factor authentication challenge.
     *
     * @return string The localized page title
     */
    public function getTitle(): string
    {
        return __('auth.two_factor.title');
    }

    public ?array $data = [];

    /**
     * Initialize the two-factor authentication challenge page.
     *
     * This method handles authentication checks, redirects authenticated users
     * without 2FA enabled or already verified, and sends verification codes
     * when needed.
     */
    public function mount(): void
    {
        // Redirect if not authenticated or 2FA not needed
        if (! Auth::check()) {
            redirect()->route('filament.admin.auth.login');

            return;
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->email_two_factor_enabled) {
            session(['email_2fa_verified' => true]);
            redirect()->intended(filament()->getUrl());

            return;
        }

        if (session('email_2fa_verified')) {
            redirect()->intended(filament()->getUrl());

            return;
        }

        // Send code if not already sent
        if (! session('email_2fa_code_sent')) {
            $code = $user->generateEmailTwoFactorCode();
            $user->notify(new EmailTwoFactorCode($code, 'login'));
            session(['email_2fa_code_sent' => true]);

            Notification::make()
                ->info()
                ->title(__('auth.two_factor.code_sent_title'))
                ->body(__('auth.two_factor.code_sent_body'))
                ->send();
        }

        $this->form->fill();
    }

    /**
     * Define the form schema for the two-factor authentication challenge.
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getCodeFormComponent(),
            ])
            ->statePath('data');
    }

    /**
     * Get the verification code input form component.
     *
     * @return \Filament\Forms\Components\Component The configured code input component
     */
    protected function getCodeFormComponent(): Component
    {
        return TextInput::make('code')
            ->label(__('auth.two_factor.code_label'))
            ->required()
            ->autofocus()
            ->length(6)
            ->numeric()
            ->placeholder('000000')
            ->helperText(__('auth.two_factor.code_helper'));
    }

    /**
     * Verify the submitted two-factor authentication code.
     *
     * This method validates the user's verification code, handles successful
     * verification by clearing the code and setting session variables,
     * or displays error notifications for invalid codes.
     */
    public function verify(): void
    {
        $data = $this->form->getState();
        /** @var User $user */
        $user = Auth::user();

        if (! $user->verifyEmailTwoFactorCode($data['code'])) {
            Notification::make()
                ->danger()
                ->title(__('auth.two_factor.invalid_code_title'))
                ->body(__('auth.two_factor.invalid_code_body'))
                ->send();

            $this->form->fill();

            return;
        }

        $user->clearEmailTwoFactorCode();
        session(['email_2fa_verified' => true, 'email_2fa_code_sent' => false]);

        redirect()->intended(filament()->getUrl());
    }

    /**
     * Resend a new verification code to the user's email.
     *
     * This method generates a fresh verification code and sends it
     * via email notification, then displays a success message.
     */
    public function resendCode(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $code = $user->generateEmailTwoFactorCode();
        $user->notify(new EmailTwoFactorCode($code, 'login'));

        Notification::make()
            ->success()
            ->title(__('auth.two_factor.code_resent_title'))
            ->body(__('auth.two_factor.code_resent_body'))
            ->send();
    }

    /**
     * Determine if form actions should span the full width.
     *
     * @return bool True to make form actions full width
     */
    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }
}
