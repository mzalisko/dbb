<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('local', 'testing')) {
            $admin = User::factory()->create([
                'name' => 'Admin',
                'email' => 'admin@databridge.local',
                'password' => bcrypt('password'),
                'role' => 'owner',
                'organization_name' => 'DataBridge Agency',
            ]);

            $member = User::factory()->create([
                'name' => 'Team Member',
                'email' => 'member@databridge.local',
                'password' => bcrypt('password'),
                'role' => 'member',
            ]);

            // 5 clients with sites for admin
            Client::factory(5)
                ->for($admin)
                ->has(Site::factory()->count(rand(1, 4)))
                ->create();

            // 2 clients for member
            Client::factory(2)
                ->for($member)
                ->has(Site::factory()->count(rand(1, 2)))
                ->create();
        }
    }
}
