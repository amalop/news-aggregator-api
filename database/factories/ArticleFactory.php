<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Article;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Article::class;

    public function definition()
    {
        $now = now();
        $title = $this->faker->sentence;
        return [
            'title' => $this->faker->sentence,
            'article_identifier'=>md5($title .'1'.$now),
            'content' => $this->faker->paragraph,
            'category_id' => 1,
            'source_id' => 1,
            'author_id' => 1,
            'published_at'=>$now
        ];
    }
}