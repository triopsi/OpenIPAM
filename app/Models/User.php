<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'gravatar_type',
        'language',
        'email_two_factor_enabled',
        'email_two_factor_code',
        'email_two_factor_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_two_factor_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_two_factor_enabled' => 'boolean',
            'email_two_factor_expires_at' => 'datetime',
        ];
    }

    /**
     * Get the user's avatar URL for Filament.
     *
     * @return string|null Get the user's avatar URL for Filament.
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->getGravatarUrlAttribute();
    }

    /**
     * Get the user's gravatar URL
     */
    public function getGravatarUrlAttribute(): string
    {
        $hash = md5(strtolower(trim($this->email)));
        $default = $this->gravatar_type ?? 'mp';

        return "https://www.gravatar.com/avatar/{$hash}?d={$default}&s=40";
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Determine if the user can access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true; // Allow all authenticated users to access the admin panel
    }

    /**
     * Generate a new email two-factor authentication code.
     */
    public function generateEmailTwoFactorCode(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->email_two_factor_code = $code;
        $this->email_two_factor_expires_at = now()->addMinutes(10);
        $this->save();

        return $code;
    }

    /**
     * Verify the email two-factor authentication code.
     */
    public function verifyEmailTwoFactorCode(string $code): bool
    {
        if (! $this->email_two_factor_code || ! $this->email_two_factor_expires_at) {
            return false;
        }

        if ($this->email_two_factor_expires_at->isPast()) {
            return false;
        }

        return $this->email_two_factor_code === $code;
    }

    /**
     * Clear the email two-factor authentication code.
     */
    public function clearEmailTwoFactorCode(): void
    {
        $this->email_two_factor_code = null;
        $this->email_two_factor_expires_at = null;
        $this->save();
    }
}
