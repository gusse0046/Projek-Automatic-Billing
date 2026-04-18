<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Buat user spesifik sesuai requirement
        $users = [
            [
                'name' => 'Finance Admin',
                'email' => 'finance@example.com',
                'password' => Hash::make('123finance'),
                'user_type' => 'admin-finance',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Exim User',
                'email' => 'exim@example.com',
                'password' => Hash::make('exim123'),
                'user_type' => 'exim',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Admin Setting',
                'email' => 'admin_setting@example.com',
                'password' => Hash::make('setting123'),
                'user_type' => 'setting-document',
                'email_verified_at' => now(),
            ]
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        // Update user test yang lama juga (untuk backward compatibility)
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => Hash::make('password123'),
                'user_type' => 'admin-finance', // default
                'email_verified_at' => now(),
            ]
        );
    }
}