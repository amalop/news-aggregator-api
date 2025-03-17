<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Author;

class AuthorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Author::firstOrCreate(['name' => 'Unknown']);
        $this->command->info('✅ Author seeded successfully!');   

    }
}
