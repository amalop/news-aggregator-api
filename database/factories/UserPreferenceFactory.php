<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class UserPreferenceFactory extends Factory
{
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'preferred_categories' => [],
            'preferred_sources' => [],
            'preferred_authors' => [],
        ];
    }
}