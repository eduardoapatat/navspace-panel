<?php

namespace Database\Seeders;

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
        // The administrator is created without the factory so seeding also works
        // on installs without development dependencies, such as the Docker image,
        // where Faker is not available. The password cast hashes it on save.
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Administrador NavSpace',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        $this->call(DiciBuildingSeeder::class);
    }
}
