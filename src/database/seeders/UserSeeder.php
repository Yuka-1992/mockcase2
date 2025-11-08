<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 管理者（nameはNULL、メールはadminが分かるもの）
        User::create([
            'name' => null,
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // 一般ユーザー6名（name必須）
        foreach (range(1, 6) as $i) {
            User::create([
                'name' => "ユーザー{$i}",
                'email' => "user{$i}@example.com",
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]);
        }
    }
}