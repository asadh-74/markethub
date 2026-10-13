<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

class BootstrapAdmin extends Command
{
    protected $signature = 'markethub:bootstrap-admin';

    protected $description = 'Create the first administrator using temporary environment secrets';

    public function handle(): int
    {
        $email = trim((string) (getenv('MARKETHUB_ADMIN_EMAIL') ?: ''));
        $password = (string) (getenv('MARKETHUB_ADMIN_PASSWORD') ?: '');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
            $this->error('Set a valid MARKETHUB_ADMIN_EMAIL and a password of at least 12 characters.');
            return self::FAILURE;
        }

        $existing = User::query()->where('email', $email)->first();
        if ($existing !== null) {
            if (! $existing->isAdmin()) {
                $this->error('The requested address belongs to a non-admin account; refusing to elevate its privileges.');
                return self::FAILURE;
            }

            $this->info('Administrator already exists; password was not changed.');
            return self::SUCCESS;
        }

        User::create([
            'name' => 'MarketHub Admin',
            'email' => $email,
            'password' => $password,
            'role' => UserRole::Admin->value,
        ]);

        $this->info('Initial administrator created. Remove the temporary bootstrap credentials from hosting settings.');

        return self::SUCCESS;
    }
}
