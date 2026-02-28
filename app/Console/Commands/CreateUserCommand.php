<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateUserCommand extends Command
{
    protected $signature = 'user:create
                            {--name= : User name}
                            {--email= : User email}
                            {--password= : User password (min 8 chars)}
                            {--admin : Make the user an administrator}';

    protected $description = 'Create or update a user (by email). Safe for production.';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Name');
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->option('password');
        $isAdmin = $this->option('admin');

        if (empty($name) || empty($email)) {
            $this->error('Name and email are required.');
            return self::FAILURE;
        }

        $email = strtolower($email);

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            [
                'email' => ['required', 'email'],
                'password' => ['nullable', 'string', 'min:8'],
            ]
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }
            return self::FAILURE;
        }

        if ($password === null || $password === '') {
            $password = $this->secret('Password (min 8 characters)');
            $confirm = $this->secret('Confirm password');
            if ($password !== $confirm) {
                $this->error('Passwords do not match.');
                return self::FAILURE;
            }
            if (strlen($password) < 8) {
                $this->error('Password must be at least 8 characters.');
                return self::FAILURE;
            }
        }

        if (!$isAdmin && !$this->option('password')) {
            $isAdmin = $this->confirm('Make this user an administrator?', false);
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'is_admin' => $isAdmin,
                'blocked_at' => null,
            ]
        );

        $this->info($user->wasRecentlyCreated
            ? "User created: {$user->email}" . ($isAdmin ? ' (admin)' : '')
            : "User updated: {$user->email}" . ($isAdmin ? ' (admin)' : ''));

        return self::SUCCESS;
    }
}
