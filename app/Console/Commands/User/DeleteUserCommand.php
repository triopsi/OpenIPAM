<?php

namespace App\Console\Commands\User;

use App\Models\User;
use Illuminate\Console\Command;

class DeleteUserCommand extends Command
{
    protected $signature = 'user:delete
                            {user : Email or ID of the user}
                            {--force : Delete without confirmation}';

    protected $description = 'Delete a user';

    public function handle()
    {
        $userInput = $this->argument('user');

        // Benutzer finden
        $user = $this->findUser($userInput);
        if (! $user) {
            return 1;
        }

        $this->info("User: {$user->name} ({$user->email})");
        $this->line("Created: {$user->created_at->format('d.m.Y H:i')}");

        if (! $this->option('force')) {
            if (! $this->confirm('Are you sure you want to delete this user?')) {
                $this->info('Cancelled.');

                return 0;
            }
        }

        try {
            $userName = $user->name;
            $user->delete();

            $this->info("User '{$userName}' has been deleted successfully.");

            return 0;
        } catch (\Exception $e) {
            $this->error("Error deleting user: {$e->getMessage()}");

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
