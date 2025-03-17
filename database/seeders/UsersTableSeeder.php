<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Retrieve or create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Create or update admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'], // Check if user exists by email
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'), // Use a secure password
            ]
        );
        $admin->syncRoles($adminRole); // Assign the admin role

        // Create or update regular user
        $user = User::firstOrCreate(
            ['email' => 'user@example.com'], // Check if user exists by email
            [
                'name' => 'Regular User',
                'password' => Hash::make('password'), // Use a secure password
            ]
        );
        $user->syncRoles($userRole); // Assign the user role
        $this->command->info('✅ Users seeded successfully!');   

    }
}