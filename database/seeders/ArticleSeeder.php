<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use App\Models\Author;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Create example categories, sources, and authors
        $category = Category::firstOrCreate(['name' => 'Technology']);
        $source = Source::firstOrCreate(['name' => 'Tech News']);
        $author = Author::firstOrCreate(['name' => 'John Doe']);

        // Create example articles
        Article::factory()->count(10)->create([
            'category_id' => $category->id,
            'source_id' => $source->id,
            'author_id' => $author->id,
        ]);
    }
}