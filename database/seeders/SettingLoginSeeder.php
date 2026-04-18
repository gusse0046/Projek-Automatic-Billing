<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SettingLogin;

class SettingLoginSeeder extends Seeder
{
    public function run()
    {
        SettingLogin::create([
            'username' => 'admin_setting',
            'password' => 'setting123',
            'name' => 'Admin Setting Document',
            'is_active' => true
        ]);
    }
}