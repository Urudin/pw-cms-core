<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Filament\Models\Contracts\FilamentUser;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Create a random user for Filament Admin
        User::factory()->create([
            'name' => 'Filament Admin',
            'email' => 'admin@example.com', // Change to whatever email you prefer
            'password' => bcrypt('password'), // Change password if needed
        ]); // Ensure you have assigned roles properly in your app
    }
}
