<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create predefined test users
        $testUsers = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => 'password',
                'is_admin' => true,
                'avatar' => 'https://ui-avatars.com/api/?name=Admin+User&background=0D8ABC&color=fff',
            ],
            [
                'name' => 'Editor User',
                'email' => 'editor@example.com',
                'password' => 'password',
                'is_admin' => false,
                'avatar' => 'https://ui-avatars.com/api/?name=Editor+User&background=2ECC71&color=fff',
            ],
            [
                'name' => 'Regular User',
                'email' => 'user@example.com',
                'password' => 'password',
                'is_admin' => false,
                'avatar' => 'https://ui-avatars.com/api/?name=Regular+User&background=9B59B6&color=fff',
            ],
            [
                'name' => 'Hacker User',
                'email' => 'hacker@example.com',
                'password' => 'password',
                'is_admin' => false,
                'avatar' => 'https://ui-avatars.com/api/?name=Hacker+User&background=E74C3C&color=fff',
            ],
        ];

        foreach ($testUsers as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make($user['password']),
                    'is_admin' => $user['is_admin'],
                    'avatar' => $user['avatar'],
                    'email_verified_at' => now(),
                ]
            );
        }

        // Create additional random users
        User::factory(5)->create();
        User::factory(2)->admin()->create();
    }
} 
