<?php

namespace App\Console\Commands\User;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateUserCommand extends Command
{
    protected $signature = 'user:create
                            {--name= : Name of the user}
                            {--email= : Email address of the user}
                            {--password= : Password of the user}
                            {--gravatar-type=mp : Gravatar type (mp, identicon, monsterid, wavatar, retro, robohash, blank)}';

    protected $description = 'Create a new user';

    public function handle()
    {
        $name = $this->option('name') ?: $this->ask('User name');
        $email = $this->option('email') ?: $this->ask('Email address');
        $password = $this->option('password') ?: $this->secret('Password');
        $gravatarType = $this->option('gravatar-type') ?: $this->choice(
            'Gravatar type',
            ['mp', 'identicon', 'monsterid', 'wavatar', 'retro', 'robohash', 'blank'],
            'mp'
        );

        // Validation
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            $this->error('Validation errors:');
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return 1;
        }

        try {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'gravatar_type' => $gravatarType,
                'email_two_factor_enabled' => false,
            ]);

            $this->info("User '{$user->name}' has been created successfully.");
            $this->line("ID: {$user->id}");
            $this->line("Email: {$user->email}");
            $this->line("Gravatar type: {$user->gravatar_type}");

            return 0;
        } catch (\Exception $e) {
            $this->error("Error creating user: {$e->getMessage()}");

            return 1;
        }
    }
}
