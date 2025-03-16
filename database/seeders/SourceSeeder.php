<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Source;

class SourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sources = ['New York Times','The Guardian','NEWS API'];
        foreach ($sources as $source){
            Source::updateOrCreate(['name' => $source]);
        }
        $this->command->info('✅ Sources seeded successfully!');   
    }
}
