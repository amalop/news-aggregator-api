<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use App\Models\Author;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserPreferenceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure required permissions exist
        if (!Permission::where('name', 'preferences.create')->exists()) {
            Permission::updateOrCreate(['name' => 'preferences.create']);
        }

        if (!Permission::where('name', 'preferences.view')->exists()) {
            Permission::updateOrCreate(['name' => 'preferences.view']);
        }
    }

    /**
     * Test updating user preferences.
     */
    public function test_update_user_preferences()
    {
        // Create a user and authenticate
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Assign the required permission
        $user->givePermissionTo('preferences.create');

        // Create required categories, sources, and authors if they don’t exist
        $category = Category::firstOrCreate(['name' => 'Technology']);
        $source = Source::firstOrCreate(['name' => 'TechCrunch']);
        $author = Author::firstOrCreate(['name' => 'John Doe']);

        // Send a PUT request to update preferences
        $response = $this->putJson('/api/preferences', [
            'preferred_categories' => [$category->id],
            'preferred_sources' => [$source->id],
            'preferred_authors' => [$author->id],
        ]);

        // Assert the response status is 200 (OK)
        $response->assertStatus(200);

        // Assert the response contains the success message
        $response->assertJson([
            'message' => 'Preferences updated successfully',
        ]);

        // Assert the preferences were saved in the database
        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'preferred_categories' => json_encode([$category->id]),
            'preferred_sources' => json_encode([$source->id]),
            'preferred_authors' => json_encode([$author->id]),
        ]);
    }

    /**
     * Test fetching user preferences and personalized news feed.
     */
    public function test_fetch_user_preferences_and_news_feed()
    {
        // Create a user and authenticate
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Ensure the permission exists and assign it
        $user->givePermissionTo('preferences.view');

        // Create categories, sources, and authors
        $category = Category::firstOrCreate(['name' => 'Technology']);
        $source = Source::firstOrCreate(['name' => 'TechCrunch']);
        $author = Author::firstOrCreate(['name' => 'John Doe']);

        // Create preferences for the user
        UserPreference::factory()->create([
            'user_id' => $user->id,
            'preferred_categories' => [$category->id],
            'preferred_sources' => [$source->id],
            'preferred_authors' => [$author->id],
        ]);

        // Create articles matching preferences
        Article::factory()->create([
            'category_id' => $category->id,
            'source_id' => $source->id,
            'author_id' => $author->id,
        ]);

        // Send a GET request to fetch preferences and news feed
        $response = $this->getJson('/api/preferences');

        // Assert the response status is 200 (OK)
        $response->assertStatus(200);

        // Assert the response contains articles
        $response->assertJsonStructure(['data' => ['data']]);
    }

    /**
     * Test fetching user preferences when no preferences are set.
     */
    public function test_fetch_user_preferences_when_none_exist()
    {
        // Create a user and authenticate
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Ensure the permission exists and assign it
        $user->givePermissionTo('preferences.view');

        // Send a GET request to fetch preferences
        $response = $this->getJson('/api/preferences');

        // Assert the response status is 200 (OK)
        $response->assertStatus(200);

        // Assert the response contains the message for no preferences
        $response->assertJson(['message' => 'No personalized preferences set']);
    }

    /**
     * Test updating user preferences with invalid data.
     */
    public function test_update_user_preferences_with_invalid_data()
    {
        // Create a user and authenticate
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Ensure the permission exists and assign it
        $user->givePermissionTo('preferences.create');

        // Send a PUT request with invalid data
        $response = $this->putJson('/api/preferences', [
            'preferred_categories' => [999], // Invalid category ID
            'preferred_sources' => [999], // Invalid source ID
            'preferred_authors' => [999], // Invalid author ID
        ]);

        // Assert the response status is 422 (Validation error)
        $response->assertStatus(422);

        // Assert the response contains validation errors
        $response->assertJsonValidationErrors([
            'preferred_categories.0',
            'preferred_sources.0',
            'preferred_authors.0',
        ]);
    }

    /**
     * Test unauthorized access to update preferences.
     */
    public function test_unauthorized_update_user_preferences()
    {
        // Create a user without the required permission
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Send a PUT request to update preferences
        $response = $this->putJson('/api/preferences', [
            'preferred_categories' => [1],
            'preferred_sources' => [2],
            'preferred_authors' => [3],
        ]);

        // Assert the response status is 403 (Forbidden)
        $response->assertStatus(403);

        // Assert the response contains the unauthorized message
        $response->assertJson(['message' => 'Unauthorized']);
    }

    /**
     * Test unauthorized access to fetch preferences.
     */
    public function test_unauthorized_fetch_user_preferences()
    {
        // Create a user without the required permission
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Send a GET request to fetch preferences
        $response = $this->getJson('/api/preferences');

        // Assert the response status is 403 (Forbidden)
        $response->assertStatus(403);

        // Assert the response contains the unauthorized message
        $response->assertJson(['message' => 'Unauthorized']);
    }
}
