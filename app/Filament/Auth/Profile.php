<?php

namespace App\Filament\Auth;

use App\Models\User;
use App\Notifications\EmailTwoFactorCode;
use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class Profile extends EditProfile
{
    /**
     * Get the page title for the profile page.
     *
     * @return string The localized page title
     */
    public function getTitle(): string
    {
        return __('common.navigation.profile');
    }

    public ?array $profileData = [];

    public ?array $passwordData = [];

    public ?array $twoFactorData = [];

    public bool $isVerifying = false;

    /**
     * Use Filament's standalone profile layout with a separate form for each category.
     */
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('profile')
                ->persistTabInQueryString('tab')
                ->tabs([
                    Tab::make(__('profile.tabs.account'))
                        ->id('account')
                        ->key('account', isInheritable: false)
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Form::make([EmbeddedSchema::make('profileForm')])
                                ->id('profile-form')
                                ->livewireSubmitHandler('updateProfile')
                                ->footer([
                                    Actions::make([
                                        Action::make('saveProfile')
                                            ->label(__('profile.save_profile'))
                                            ->submit('updateProfile'),
                                    ]),
                                ]),
                        ]),
                    Tab::make(__('profile.tabs.password'))
                        ->id('password')
                        ->key('password', isInheritable: false)
                        ->icon('heroicon-o-key')
                        ->schema([
                            Form::make([EmbeddedSchema::make('passwordForm')])
                                ->id('password-form')
                                ->livewireSubmitHandler('updatePassword')
                                ->footer([
                                    Actions::make([
                                        Action::make('savePassword')
                                            ->label(__('profile.change_password'))
                                            ->submit('updatePassword'),
                                    ]),
                                ]),
                        ]),
                    Tab::make(__('profile.tabs.security'))
                        ->id('security')
                        ->key('security', isInheritable: false)
                        ->icon('heroicon-o-shield-check')
                        ->schema([EmbeddedSchema::make('twoFactorForm')]),
                ]),
            Actions::make([
                Action::make('backToDashboard')
                    ->label(__('profile.back_to_dashboard'))
                    ->icon('heroicon-o-arrow-left')
                    ->url(fn (): string => filament()->getUrl())
                    ->color('gray'),
            ]),
        ]);
    }

    public function getMaxWidth(): Width|string|null
    {
        return Width::FourExtraLarge;
    }

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
     * Get the profile information form.
     *
     * @return Schema The profile form with personal data fields
     */
    public function profileForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->contained(false)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('common.fields.name'))
                            ->required()
                            ->prefixIcon('heroicon-o-user')
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('common.fields.email'))
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->prefixIcon('heroicon-o-envelope')
                            ->required()
                            ->maxLength(255),
                        Select::make('language')
                            ->label(__('common.navigation.language'))
                            ->options(collect(config('languages.supported'))->mapWithKeys(fn ($lang, $code) => [
                                $code => $lang['native_name'],
                            ]))
                            ->default(app()->getLocale())
                            ->required()
                            ->prefixIcon('heroicon-o-language')
                            ->helperText(__('common.fields.language_helper')),
                        Select::make('gravatar_type')
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
                    ->columns(1),
            ])
            ->inlineLabel()
            ->model($this->getUser())
            ->statePath('profileData');
    }

    /**
     * Get the password change form.
     *
     * @return Schema The password form with validation rules
     */
    public function passwordForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->contained(false)
                    ->schema([
                        TextInput::make('current_password')
                            ->label(__('profile.current_password'))
                            ->password()
                            ->required()
                            ->currentPassword()
                            ->prefixIcon('heroicon-o-key')
                            ->columnSpanFull(),
                        TextInput::make('password')
                            ->label(__('profile.new_password'))
                            ->password()
                            ->required()
                            ->rule(Password::default())
                            ->prefixIcon('heroicon-o-lock-closed')
                            ->confirmed(),
                        TextInput::make('password_confirmation')
                            ->label(__('profile.new_password_confirmation'))
                            ->password()
                            ->required()
                            ->prefixIcon('heroicon-o-lock-closed')
                            ->dehydrated(false),
                    ])
                    ->columns(1),
            ])
            ->inlineLabel()
            ->statePath('passwordData');
    }

    /**
     * Get the two-factor authentication form.
     *
     * Returns different form schemas based on verification state.
     *
     * @return Schema The 2FA form (status or verification)
     */
    public function twoFactorForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('profile.enter_code'))
                    ->contained(false)
                    ->visible(fn (): bool => $this->isVerifying)
                    ->description(__('profile.enter_code_description'))
                    ->schema([
                        TextInput::make('verification_code')
                            ->label(__('profile.verification_code'))
                            ->required()
                            ->length(6)
                            ->regex('/^[0-9]{6}$/')
                            ->inputMode('numeric')
                            ->autocomplete('one-time-code')
                            ->placeholder('000000')
                            ->autofocus(),
                    ])
                    ->headerActions([
                        Action::make('verify')
                            ->label(__('profile.verify'))
                            ->action('verifyAndDisable')
                            ->color('primary'),
                        Action::make('cancel')
                            ->label(__('common.actions.cancel'))
                            ->action('cancelVerification')
                            ->color('gray'),
                    ]),
                Section::make(__('profile.two_factor_auth'))
                    ->contained(false)
                    ->hidden(fn (): bool => $this->isVerifying)
                    ->description(__('profile.two_factor_description'))
                    ->schema([
                        TextEntry::make('status')
                            ->label(__('common.fields.status'))
                            ->state(fn () => Auth::user()->email_two_factor_enabled
                                ? __('profile.enabled')
                                : __('profile.disabled')
                            ),
                        TextEntry::make('email')
                            ->label(__('common.fields.email'))
                            ->state(fn () => Auth::user()->email),
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
        $languageChanged = $user->language !== $data['language'];
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->language = $data['language'];
        $user->gravatar_type = $data['gravatar_type'];
        $user->save();

        // Set application locale immediately
        app()->setLocale($data['language']);
        session()->put('locale', $data['language']);

        Notification::make()
            ->title(__('profile.profile_updated'))
            ->success()
            ->send();

        if ($languageChanged) {
            $this->redirect(static::getUrl(parameters: ['tab' => 'account']));
        }
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
            ->title(__('profile.enabled_title'))
            ->body(__('profile.2fa_enabled_success'))
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
            ->title(__('profile.code_sent_title'))
            ->body(__('profile.verification_sent'))
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
                ->title(__('profile.invalid_code_title'))
                ->body(__('profile.invalid_or_expired_code'))
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
            ->title(__('profile.disabled_title'))
            ->body(__('profile.2fa_disabled_success'))
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
     * @return array<Action> Array of form actions
     */
    protected function getTwoFactorActions(): array
    {
        return [
            Action::make('disable')
                ->visible(fn (): bool => (bool) Auth::user()->email_two_factor_enabled)
                ->label(__('profile.disable'))
                ->action('requestDisableCode')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('profile.disable_2fa'))
                ->modalDescription(__('profile.disable_confirmation'))
                ->modalSubmitActionLabel(__('profile.send_code')),
            Action::make('enable')
                ->hidden(fn (): bool => (bool) Auth::user()->email_two_factor_enabled)
                ->label(__('profile.enable'))
                ->action('enableTwoFactor')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('profile.enable_2fa'))
                ->modalDescription(__('profile.enable_confirmation'))
                ->modalSubmitActionLabel(__('profile.enable')),
        ];
    }
}
