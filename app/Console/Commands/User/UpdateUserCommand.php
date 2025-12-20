<?php

namespace App\Console\Commands\User;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UpdateUserCommand extends Command
{
    protected $signature = 'user:update
                            {user : Email or ID of the user}
                            {--name= : New name}
                            {--email= : New email address}
                            {--password= : New password}
                            {--gravatar-type= : New gravatar type}
                            {--reset-2fa : Reset 2FA}';

    protected $description = 'Update an existing user';

    public function handle()
    {
        $userInput = $this->argument('user');

        // Benutzer finden
        $user = $this->findUser($userInput);
        if (! $user) {
            return 1;
        }

        $this->info("Current user: {$user->name} ({$user->email})");

        $changes = [];

        // Change name
        if ($this->option('name') || $this->confirm('Change name?', false)) {
            $newName = $this->option('name') ?: $this->ask('New name', $user->name);
            if ($newName !== $user->name) {
                $changes['name'] = $newName;
            }
        }

        // Change email
        if ($this->option('email') || $this->confirm('Change email address?', false)) {
            $newEmail = $this->option('email') ?: $this->ask('New email address', $user->email);
            if ($newEmail !== $user->email) {
                // Validation
                $validator = Validator::make(['email' => $newEmail], [
                    'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
                ]);

                if ($validator->fails()) {
                    $this->error('Invalid email address or already taken.');

                    return 1;
                }

                $changes['email'] = $newEmail;
            }
        }

        // Reset password
        if ($this->option('password') || $this->confirm('Reset password?', false)) {
            $newPassword = $this->option('password') ?: $this->secret('New password');

            if (strlen($newPassword) < 8) {
                $this->error('Password must be at least 8 characters.');

                return 1;
            }

            $changes['password'] = Hash::make($newPassword);
        }

        // Change gravatar type
        if ($this->option('gravatar-type') || $this->confirm('Change gravatar type?', false)) {
            $newGravatarType = $this->option('gravatar-type') ?: $this->choice(
                'New gravatar type',
                ['mp', 'identicon', 'monsterid', 'wavatar', 'retro', 'robohash', 'blank'],
                $user->gravatar_type
            );
            if ($newGravatarType !== $user->gravatar_type) {
                $changes['gravatar_type'] = $newGravatarType;
            }
        }

        // Reset 2FA
        if ($this->option('reset-2fa') || ($user->email_two_factor_enabled && $this->confirm('Reset 2FA?', false))) {
            $changes['email_two_factor_enabled'] = false;
            $changes['email_two_factor_code'] = null;
            $changes['email_two_factor_expires_at'] = null;
        }

        if (empty($changes)) {
            $this->info('No changes made.');

            return 0;
        }

        try {
            $user->update($changes);

            $this->info("User '{$user->name}' has been updated successfully.");

            foreach ($changes as $field => $value) {
                if ($field === 'password') {
                    $this->line('- Password has been changed');
                } elseif ($field === 'email_two_factor_enabled' && ! $value) {
                    $this->line('- 2FA has been reset');
                } elseif (! in_array($field, ['email_two_factor_code', 'email_two_factor_expires_at'])) {
                    $this->line("- {$field}: {$value}");
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Error updating user: {$e->getMessage()}");

            return 1;
        }
    }

    private function findUser($input)
    {
        // Versuche als E-Mail zu finden
        $user = User::where('email', $input)->first();

        // Versuche als ID zu finden
        if (! $user && is_numeric($input)) {
            $user = User::find($input);
        }

        if (! $user) {
            $this->error("User '{$input}' not found.");

            return null;
        }

        return $user;
    }
}
