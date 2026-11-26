<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'rishtahub:make-admin {email : Email of the admin}';

    protected $description = 'Create an admin user, or promote an existing user to admin';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('That is not a valid email address.');
            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->role = 'admin';
            $user->email_verified_at = $user->email_verified_at ?? now();
            $user->save();
            $this->info("{$email} is now an admin.");
            return self::SUCCESS;
        }

        $password = (string) $this->secret('Password for the new admin (min 8 characters)');

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');
            return self::FAILURE;
        }

        $user = new User();
        $user->email = $email;
        $user->password = $password;
        $user->role = 'admin';
        $user->email_verified_at = now(); // admins do not need to click an email link
        $user->save();

        $this->info("Admin {$email} created.");

        return self::SUCCESS;
    }
}
