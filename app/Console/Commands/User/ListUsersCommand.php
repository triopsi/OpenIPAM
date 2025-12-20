<?php

namespace App\Console\Commands\User;

use App\Models\User;
use Illuminate\Console\Command;

class ListUsersCommand extends Command
{
    protected $signature = 'user:list
                            {--filter= : Filter by name or email}
                            {--with-2fa : Show only users with 2FA}
                            {--without-2fa : Show only users without 2FA}';

    protected $description = 'List all users';

    public function handle()
    {
        $query = User::query();

        // Filter anwenden
        if ($filter = $this->option('filter')) {
            $query->where(function ($q) use ($filter) {
                $q->where('name', 'like', "%{$filter}%")
                    ->orWhere('email', 'like', "%{$filter}%");
            });
        }

        if ($this->option('with-2fa')) {
            $query->where('email_two_factor_enabled', true);
        } elseif ($this->option('without-2fa')) {
            $query->where('email_two_factor_enabled', false);
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        if ($users->isEmpty()) {
            $this->info('No users found.');

            return 0;
        }

        $headers = ['ID', 'Name', 'Email', '2FA', 'Gravatar', 'Created'];
        $rows = [];

        foreach ($users as $user) {
            $rows[] = [
                $user->id,
                $user->name,
                $user->email,
                $user->email_two_factor_enabled ? '✓' : '✗',
                $user->gravatar_type,
                $user->created_at->format('d.m.Y H:i'),
            ];
        }

        $this->table($headers, $rows);
        $this->info("Found: {$users->count()} users");

        return 0;
    }
}
