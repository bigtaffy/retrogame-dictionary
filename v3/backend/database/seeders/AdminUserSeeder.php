<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Filament 後台登入用（與 v3/README 說明一致）
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@local.test'],
            [
                'name' => 'admin',
                'password' => Hash::make('123Qwe'),
                'email_verified_at' => now(),
            ],
        );
    }
}
