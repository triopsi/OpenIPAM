<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Notifications\EmailTwoFactorCode;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class Profile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static string $view = 'filament.pages.profile';

    protected static ?string $title = null;

    protected static ?string $navigationLabel = null;

    /**
     * Get the navigation label for the profile page.
     *
     * @return string The localized navigation label
     */
    public static function getNavigationLabel(): string
    {
        return __('common.navigation.profile');
    }

    /**
     * Get the page title for the profile page.
     *
     * @return string The localized page title
     */
    public function getTitle(): string
    {
        return __('common.navigation.profile');
    }

    protected static bool $shouldRegisterNavigation = false;

    public ?array $profileData = [];

    public ?array $passwordData = [];

    public ?array $twoFactorData = [];

    public bool $isVerifying = false;

    /**
     * Initialize the profile page by filling all forms.
     */
    public function mount(): void
    {
        $this->fillForms();
    }

    /**
     * Fill all forms with current user data.
     *
     * This method populates the profile, password, and two-factor
     * authentication forms with the authenticated user's information.
     */
    protected function fillForms(): void
    {
        $user = Auth::user();

        $this->profileForm->fill([
            'name' => $user->name,
            'email' => $user->email,
            'language' => $user->language ?? app()->getLocale(),
            'gravatar_type' => $user->gravatar_type ?? 'mp',
        ]);

        $this->passwordForm->fill();

        $this->twoFactorForm->fill([
            'two_factor_enabled' => $user->email_two_factor_enabled ?? false,
        ]);
    }

    /**
     * Get all forms available on this page.
     *
     * @return array<string, \Filament\Forms\Form> Array of form instances
     */
    protected function getForms(): array
    {
        return [
            'profileForm' => $this->getProfileForm(),
            'passwordForm' => $this->getPasswordForm(),
            'twoFactorForm' => $this->getTwoFactorForm(),
        ];
    }

    /**
     * Get the profile information form.
     *
     * @return \Filament\Forms\Form The profile form with personal data fields
     */
    protected function getProfileForm(): Form
    {
        return $this->makeForm()
            ->schema([
                Section::make(__('common.fields.personal_data'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('common.fields.name'))
                            ->required()
                            ->suffixIcon('heroicon-o-user')
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('common.fields.email'))
                            ->email()
                            ->suffixIcon('heroicon-o-envelope')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\Select::make('language')
                            ->label(__('common.navigation.language'))
                            ->options(collect(config('languages.supported'))->mapWithKeys(fn ($lang, $code) => [
                                $code => $lang['native_name'],
                            ]))
                            ->default(app()->getLocale())
                            ->required()
                            ->suffixIcon('heroicon-o-language')
                            ->helperText(__('common.fields.language_helper')),
                        \Filament\Forms\Components\Select::make('gravatar_type')
                            ->label(__('common.fields.gravatar_type'))
                            ->options([
                                'mp' => __('common.fields.gravatar_options.mp'),
                                'identicon' => __('common.fields.gravatar_options.identicon'),
                                'monsterid' => __('common.fields.gravatar_options.monsterid'),
                                'wavatar' => __('common.fields.gravatar_options.wavatar'),
                                'retro' => __('common.fields.gravatar_options.retro'),
                                'robohash' => __('common.fields.gravatar_options.robohash'),
                                'blank' => __('common.fields.gravatar_options.blank'),
                            ])
                            ->default('mp')
                            ->helperText(__('common.fields.gravatar_helper')),
                    ])
                    ->columns(2),
            ])
            ->statePath('profileData');
    }

    /**
     * Get the password change form.
     *
     * @return \Filament\Forms\Form The password form with validation rules
     */
    protected function getPasswordForm(): Form
    {
        return $this->makeForm()
            ->schema([
                Section::make(__('profile.change_password'))
                    ->schema([
                        TextInput::make('current_password')
                            ->label(__('profile.current_password'))
                            ->password()
                            ->required()
                            ->currentPassword()
                            ->suffixIcon('heroicon-o-key')
                            ->columnSpanFull(),
                        TextInput::make('password')
                            ->label(__('profile.new_password'))
                            ->password()
                            ->required()
                            ->rule(Password::default())
                            ->suffixIcon('heroicon-o-lock-closed')
                            ->confirmed(),
                        TextInput::make('password_confirmation')
                            ->label(__('profile.new_password_confirmation'))
                            ->password()
                            ->required()
                            ->suffixIcon('heroicon-o-lock-closed')
                            ->dehydrated(false),
                    ])
                    ->columns(2),
            ])
            ->statePath('passwordData');
    }

    /**
     * Get the two-factor authentication form.
     *
     * Returns different form schemas based on verification state.
     *
     * @return \Filament\Forms\Form The 2FA form (status or verification)
     */
    protected function getTwoFactorForm(): Form
    {
        $user = Auth::user();

        if ($this->isVerifying) {
            return $this->makeForm()
                ->schema([
                    Section::make('Code eingeben')
                        ->description('Geben Sie den 6-stelligen Code ein, den wir an Ihre E-Mail gesendet haben.')
                        ->schema([
                            TextInput::make('verification_code')
                                ->label('Verifizierungscode')
                                ->required()
                                ->length(6)
                                ->numeric()
                                ->placeholder('000000')
                                ->autofocus(),
                        ])
                        ->headerActions([
                            Action::make('verify')
                                ->label('Verifizieren')
                                ->action('verifyAndDisable')
                                ->color('primary'),
                            Action::make('cancel')
                                ->label('Abbrechen')
                                ->action('cancelVerification')
                                ->color('gray'),
                        ]),
                ])
                ->statePath('twoFactorData');
        }

        return $this->makeForm()
            ->schema([
                Section::make('E-Mail Zwei-Faktor-Authentifizierung')
                    ->description('Schützen Sie Ihr Konto mit einem Code, der per E-Mail gesendet wird.')
                    ->schema([
                        Placeholder::make('status')
                            ->label('Status')
                            ->content(fn () => $user->email_two_factor_enabled
                                ? '✓ Aktiviert'
                                : '✗ Deaktiviert'
                            ),
                        Placeholder::make('email')
                            ->label('E-Mail-Adresse')
                            ->content(fn () => $user->email),
                    ])
                    ->headerActions($this->getTwoFactorActions()),
            ])
            ->statePath('twoFactorData');
    }

    /**
     * Update the user's profile information.
     *
     * This method updates the user's name, email, language preference,
     * and avatar settings, then applies the new locale immediately.
     */
    public function updateProfile(): void
    {
        $data = $this->profileForm->getState();

        /** @var User $user */
        $user = Auth::user();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->language = $data['language'];
        $user->gravatar_type = $data['gravatar_type'];
        $user->save();

        // Set application locale immediately
        app()->setLocale($data['language']);

        Notification::make()
            ->title(__('profile.profile_updated'))
            ->success()
            ->send();
    }

    /**
     * Update the user's password.
     *
     * This method validates the current password and updates it with
     * the new password, refreshing the session hash for security.
     */
    public function updatePassword(): void
    {
        $data = $this->passwordForm->getState();

        /** @var User $user */
        $user = Auth::user();
        $user->password = Hash::make($data['password']);
        $user->save();

        if (request()->hasSession()) {
            request()->session()->put('password_hash_web', Auth::user()->getAuthPassword());
        }

        $this->passwordForm->fill();

        Notification::make()
            ->title(__('profile.password_updated'))
            ->success()
            ->send();
    }

    /**
     * Enable two-factor authentication for the user.
     */
    public function enableTwoFactor(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $user->email_two_factor_enabled = true;
        $user->save();

        Notification::make()
            ->success()
            ->title('Zwei-Faktor-Authentifizierung aktiviert')
            ->body('Die E-Mail Zwei-Faktor-Authentifizierung wurde erfolgreich aktiviert.')
            ->send();
    }

    /**
     * Request a verification code to disable two-factor authentication.
     *
     * This method generates and sends a verification code via email,
     * then switches to verification mode.
     */
    public function requestDisableCode(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->email_two_factor_enabled) {
            return;
        }

        $code = $user->generateEmailTwoFactorCode();
        $user->notify(new EmailTwoFactorCode($code, 'disable'));

        $this->isVerifying = true;

        Notification::make()
            ->info()
            ->title('Code gesendet')
            ->body('Wir haben einen Verifizierungscode an Ihre E-Mail gesendet.')
            ->send();
    }

    /**
     * Verify the code and disable two-factor authentication.
     *
     * This method validates the verification code and disables 2FA
     * if the code is correct, then resets the verification state.
     */
    public function verifyAndDisable(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $code = $this->twoFactorForm->getState()['verification_code'] ?? null;

        if (! $code || ! $user->verifyEmailTwoFactorCode($code)) {
            Notification::make()
                ->danger()
                ->title('Ungültiger Code')
                ->body('Der eingegebene Code ist ungültig oder abgelaufen.')
                ->send();

            return;
        }

        $user->clearEmailTwoFactorCode();
        $user->email_two_factor_enabled = false;
        $user->save();

        $this->isVerifying = false;
        $this->fillForms();

        Notification::make()
            ->success()
            ->title('Zwei-Faktor-Authentifizierung deaktiviert')
            ->body('Die E-Mail Zwei-Faktor-Authentifizierung wurde erfolgreich deaktiviert.')
            ->send();
    }

    /**
     * Cancel the two-factor authentication verification process.
     *
     * This method clears the verification state and removes any
     * pending verification codes.
     */
    public function cancelVerification(): void
    {
        $this->isVerifying = false;
        /** @var User $user */
        $user = Auth::user();
        $user->clearEmailTwoFactorCode();
    }

    /**
     * Get the appropriate actions for two-factor authentication.
     *
     * Returns enable or disable actions based on current 2FA status.
     *
     * @return array<\Filament\Forms\Components\Actions\Action> Array of form actions
     */
    protected function getTwoFactorActions(): array
    {
        $user = Auth::user();

        if ($user->email_two_factor_enabled) {
            return [
                Action::make('disable')
                    ->label('Deaktivieren')
                    ->action('requestDisableCode')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Zwei-Faktor-Authentifizierung deaktivieren?')
                    ->modalDescription('Wir senden Ihnen einen Verifizierungscode per E-Mail, um die Deaktivierung zu bestätigen.')
                    ->modalSubmitActionLabel('Code anfordern'),
            ];
        }

        return [
            Action::make('enable')
                ->label('Aktivieren')
                ->action('enableTwoFactor')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Zwei-Faktor-Authentifizierung aktivieren?')
                ->modalDescription('Nach der Aktivierung werden Sie bei jeder Anmeldung einen Code per E-Mail erhalten.')
                ->modalSubmitActionLabel('Aktivieren'),
        ];
    }
}
