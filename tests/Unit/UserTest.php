<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_registration()
    {
        // Create the 'user' role
        Role::create(['name' => 'user']);

        // Send a POST request to the registration endpoint
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);

        // Assert the response status is 201 (Created)
        $response->assertStatus(201);

        // Assert the response contains the success message
        $response->assertJson(['message' => 'User registered successfully']);

        // Assert the user exists in the database
        $this->assertDatabaseHas('users', [
            'email' => 'johndoe@example.com',
        ]);
    }
    /**
     * Test user login.
     */
    public function test_user_login()
    {
        // Create a user
        $user = User::factory()->create([
            'email' => 'johndoe@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Send a POST request to the login endpoint
        $response = $this->postJson('/api/login', [
            'email' => 'johndoe@example.com',
            'password' => 'password123',
        ]);

        // Assert the response status is 200 (OK)
        $response->assertStatus(200);

        // Assert the response contains the success message and token
        $response->assertJson(['message' => 'Login successful']);
        $response->assertJsonStructure(['data' => ['token']]);
    }

    /**
     * Test user login with invalid credentials.
     */
    public function test_user_login_with_invalid_credentials()
    {
        // Create a user
        $user = User::factory()->create([
            'email' => 'johndoe@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Send a POST request with invalid credentials
        $response = $this->postJson('/api/login', [
            'email' => 'johndoe@example.com',
            'password' => 'wrongpassword',
        ]);

        // Assert the response status is 401 (Unauthorized)
        $response->assertStatus(401);

        // Assert the response contains the error message
        $response->assertJson(['message' => 'Invalid credentials']);
    }
}