<?php

namespace App\Console\Commands\User;

use App\Models\User;
use Illuminate\Console\Command;

class UserInfoCommand extends Command
{
    protected $signature = 'user:info
                            {user : Email or ID of the user}';

    protected $description = 'Show detailed information about a user';

    public function handle()
    {
        $userInput = $this->argument('user');

        // Benutzer finden
        $user = $this->findUser($userInput);
        if (! $user) {
            return 1;
        }

        $this->info('=== User Information ===');
        $this->line("ID: {$user->id}");
        $this->line("Name: {$user->name}");
        $this->line("Email: {$user->email}");
        $this->line("Gravatar Type: {$user->gravatar_type}");
        $this->line("Gravatar URL: {$user->gravatar_url}");
        $this->line('2FA Status: '.($user->email_two_factor_enabled ? 'Enabled' : 'Disabled'));

        if ($user->email_two_factor_enabled && $user->email_two_factor_expires_at) {
            $this->line("2FA Code expires: {$user->email_two_factor_expires_at->format('d.m.Y H:i:s')}");
        }

        $this->line("Created: {$user->created_at->format('d.m.Y H:i:s')}");
        $this->line("Updated: {$user->updated_at->format('d.m.Y H:i:s')}");

        // Show device assignments
        $deviceCount = $user->devices ?? collect();
        $this->newLine();
        $this->info('=== Statistics ===');
        $this->line('Device count: '.$deviceCount->count());

        return 0;
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
