<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use App\Models\Author;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

class ArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure required permissions exist
        if (!Permission::where('name', 'articles.view')->exists()) {
            Permission::updateOrCreate(['name' => 'articles.view']);
        }
    }

    /**
     * Test fetching a list of articles.
     */
    public function it_fetches_a_list_of_articles()
    {
        // Create a user and authenticate
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Assign the required permission
        $user->givePermissionTo('articles.view');

        // Create related models using factories
        $category = Category::factory()->create(['name' => 'Technology']);
        $source = Source::factory()->create(['name' => 'BBC News']);
        $author = Author::factory()->create(['name' => 'John Doe']);

        // Create an article
        Article::factory()->create([
            'title' => 'Laravel 10 Released',
            'category_id' => $category->id,
            'source_id' => $source->id,
            'author_id' => $author->id,
            'article_identifier' => md5("Laravel 10 Released" . $source->id . now()), // Ensuring unique value
            'published_at' => now(),
        ]);

        // Send a GET request to fetch articles
        $response = $this->getJson('/api/articles');


        // Assert the response status is 200 (OK)
        $response->assertStatus(200);

        // Assert response contains an article with expected structure
        $response->assertJsonStructure([
            'data' => [
                'data' => [
                    '*' => ['id', 'title', 'category', 'source', 'author']
                ]
            ]
        ]);
    }

    /**
     * Test fetching a single article.
     */
    public function test_fetch_single_article()
    {
        // Create a user and authenticate
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Assign the required permission
        $user->givePermissionTo('articles.view');

        // Create required category, source, and author
        $category = Category::firstOrCreate(['name' => 'Technology']);
        $source = Source::firstOrCreate(['name' => 'BBC News']);
        $author = Author::firstOrCreate(['name' => 'John Doe']);

        // Create an article
        $article = Article::factory()->create([
            'title' => 'Laravel 10 Released',
            'category_id' => $category->id,
            'source_id' => $source->id,
            'author_id' => $author->id,
            'published_at' => now(),
        ]);

        // Send a GET request to fetch the article
        $response = $this->getJson("/api/articles/{$article->id}");

        // Assert the response status is 200 (OK)
        $response->assertStatus(200);

        // Assert response contains article details
        $response->assertJsonStructure([
            'data' => ['id', 'title', 'category', 'source', 'author']
        ]);
    }

    /**
     * Test fetching an article that does not exist.
     */
    public function test_fetch_non_existent_article()
    {
        // Create a user and authenticate
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Assign the required permission
        $user->givePermissionTo('articles.view');

        // Send a GET request for an article that does not exist
        $response = $this->getJson('/api/articles/999');

        // Assert the response status is 404 (Not Found)
        $response->assertStatus(404);

        // Assert response contains error message
        $response->assertJson(['message' => 'Article not found']);
    }

    /**
     * Test unauthorized access to fetch articles.
     */
    public function test_unauthorized_fetch_list_of_articles()
    {
        // Create a user without permissions and authenticate
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Send a GET request to fetch articles
        $response = $this->getJson('/api/articles');

        // Assert the response status is 403 (Forbidden)
        $response->assertStatus(403);

        // Assert response contains unauthorized message
        $response->assertJson(['message' => 'Unauthorized']);
    }

    /**
     * Test unauthorized access to fetch a single article.
     */
    public function test_unauthorized_fetch_single_article()
    {
        // Create a user without permissions and authenticate
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create required category, source, and author
        $category = Category::firstOrCreate(['name' => 'Technology']);
        $source = Source::firstOrCreate(['name' => 'BBC News']);
        $author = Author::firstOrCreate(['name' => 'John Doe']);

        // Create an article
        $article = Article::factory()->create([
            'title' => 'Laravel 10 Released',
            'category_id' => $category->id,
            'source_id' => $source->id,
            'author_id' => $author->id,
            'published_at' => now(),
        ]);

        // Send a GET request to fetch the article
        $response = $this->getJson("/api/articles/{$article->id}");

        // Assert the response status is 403 (Forbidden)
        $response->assertStatus(403);

        // Assert response contains unauthorized message
        $response->assertJson(['message' => 'Unauthorized']);
    }
}
