<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserType;

class UserTypeSeeder extends Seeder
{
    public function run()
    {
        $userTypes = [
            ['name' => 'Admin Finance', 'slug' => 'admin-finance', 'description' => 'Administrator Finance'],
            ['name' => 'Setting Document', 'slug' => 'setting-document', 'description' => 'Setting Document Management'], // Diubah dari Admin IT
            ['name' => 'Exim', 'slug' => 'exim', 'description' => 'Export Import'],
        ];

        // Delete existing data and insert new
        UserType::truncate();
        foreach ($userTypes as $userType) {
            UserType::create($userType);
        }
    }
}