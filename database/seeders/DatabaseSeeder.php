<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Nonaktifkan constraint foreign key sementara
        Schema::disableForeignKeyConstraints();

        // Kosongkan tabel users (aman dari error constraint)
        DB::table('users')->truncate();

        // Aktifkan kembali constraint foreign key
        Schema::enableForeignKeyConstraints();

        // Insert data users default
        $users = [
            [
                'name' => 'Finance Admin',
                'email' => 'finance@example.com',
                'password' => Hash::make('123finance'),
                'user_type' => 'admin-finance',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Exim User',
                'email' => 'exim@example.com',
                'password' => Hash::make('exim123'),
                'user_type' => 'exim',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Admin Setting',
                'email' => 'admin_setting@example.com',
                'password' => Hash::make('setting123'),
                'user_type' => 'setting-document',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Test Account',
                'email' => 'test@example.com',
                'password' => Hash::make('password123'),
                'user_type' => 'admin-finance',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Logistic User',
                'email' => 'logistic@example.com',
                'password' => Hash::make('logistic123'),
                'user_type' => 'logistic',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('users')->insert($users);

        // Pesan sukses di CLI
        $this->command->info('✅ Users table seeded with correct user types!');
        $this->command->table(
            ['Name', 'Email', 'User Type', 'Password (plain)'],
            [
                ['Finance Admin', 'finance@example.com', 'admin-finance', '123finance'],
                ['Exim User', 'exim@example.com', 'exim', 'exim123'],
                ['Admin Setting', 'admin_setting@example.com', 'setting-document', 'setting123'],
                ['Test Account', 'test@example.com', 'admin-finance', 'password123'],
            ]
        );
    }
}
