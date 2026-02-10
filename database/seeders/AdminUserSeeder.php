<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $emails = config('admin.allowed_emails', []);

        foreach ($emails as $email) {
            $email = strtolower(trim((string) $email));
            if ($email === '') {
                continue;
            }

            $user = User::firstOrNew(['email' => $email]);
            if (! $user->exists) {
                $user->name = Str::headline(Str::before($email, '@'));
                $user->password = Hash::make(Str::random(40));
            }

            $user->is_admin = true;
            $user->email_verified_at = $user->email_verified_at ?: now();
            $user->save();
        }
    }
}

