<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@chatbot.test'],
            [
                'name' => 'Platform Admin',
                'user_type' => User::TYPE_ADMIN,
                'email_verified_at' => now(),
                'password' => 'password',
            ],
        );
    }
}
