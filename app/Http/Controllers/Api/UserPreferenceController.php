<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

use App\Models\Article;
use App\Models\UserPreference;

class UserPreferenceController extends BaseApiController
{
    /**
     * Update user preferences.
     *
     * @OA\Put(
     *     path="/api/preferences",
     *     summary="Update user preferences",
     *     tags={"User Preferences"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"preferred_categories", "preferred_sources", "preferred_authors"},
     *             @OA\Property(
     *                 property="preferred_categories",
     *                 type="array",
     *                 description="Array of category IDs",
     *                 @OA\Items(type="integer", example=1)
     *             ),
     *             @OA\Property(
     *                 property="preferred_sources",
     *                 type="array",
     *                 description="Array of source IDs",
     *                 @OA\Items(type="integer", example=2)
     *             ),
     *             @OA\Property(
     *                 property="preferred_authors",
     *                 type="array",
     *                 description="Array of author IDs",
     *                 @OA\Items(type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Preferences updated successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request)
    {
        try {
            // Check if the user has permission to create preferences
            if (!$request->user()->can('preferences.create')) {
                return $this->sendError('Unauthorized', [], 403);
            }

            // Validate input
            $validated = $request->validate([
                'preferred_categories' => 'nullable|array',
                'preferred_categories.*' => 'integer|exists:categories,id', // Validate each category ID
                'preferred_sources' => 'nullable|array',
                'preferred_sources.*' => 'integer|exists:sources,id', // Validate each source ID
                'preferred_authors' => 'nullable|array',
                'preferred_authors.*' => 'integer|exists:authors,id', // Validate each author ID
            ]);

            // Get the authenticated user
            $user = $request->user();

            // Update or create preferences
            $preferences = $user->preferences ?? new UserPreference();
            $preferences->preferred_categories =
                $validated['preferred_categories'] ?? [];
            $preferences->preferred_sources =
                $validated['preferred_sources'] ?? [];
            $preferences->preferred_authors =
                $validated['preferred_authors'] ?? [];
            $user->preferences()->save($preferences);

            // Clear the cache for this user's preferences
            $cacheKey = 'user_preferences_' . $user->id;
            Cache::forget($cacheKey);

            return $this->sendSuccess(
                'Preferences updated successfully',
                $preferences
            );
        } catch (ValidationException $e) {
            return $this->sendError('Validation error', $e->errors(), 422);
        } catch (QueryException $e) {
            return $this->sendError(
                'Database error',
                ['error' => $e->getMessage()],
                500
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'Something went wrong',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get user preferences and personalized news feed.
     *
     * @OA\Get(
     *     path="/api/preferences",
     *     summary="Get user preferences",
     *     tags={"User Preferences"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Preferences fetched successfully"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function show(Request $request)
    {
        try {
            // Check if the user has permission to view preferences
            if (!$request->user()->can('preferences.view')) {
                return $this->sendError('Unauthorized', [], 403);
            }
            // Fetch the user's preferences
            $preferences = $request->user()->preferences;

            // Generate a unique cache key for the user's preferences
            $cacheKey = 'user_preferences_' . $request->user()->id;

            // Check if the data is already cached
            if (Cache::has($cacheKey)) {
                // Return cached data
                $articles = Cache::get($cacheKey);
                return $this->sendSuccess(
                    'Personalized news feed fetched successfully (cached)',
                    $articles
                );
            }
            // If no preferences are set, return null
            if (!$preferences) {
                return $this->sendSuccess(
                    'No personalized preferences set',
                    null
                );
            }

            // Initialize the query
            $query = Article::query();

            // Filter by preferred categories
            if (!empty($preferences->preferred_categories)) {
                $query->whereIn(
                    'category_id',
                    $preferences->preferred_categories
                );
            }

            // Filter by preferred sources
            if (!empty($preferences->preferred_sources)) {
                $query->whereIn('source_id', $preferences->preferred_sources);
            }

            // Filter by preferred authors
            if (!empty($preferences->preferred_authors)) {
                $query->whereIn('author_id', $preferences->preferred_authors);
            }

            // Fetch the filtered articles
            $articles = $query
                ->with(['category', 'source', 'author'])
                ->orderBy('published_at', 'desc')
                ->paginate(10);
            // Cache the results for 60 minutes
            Cache::put($cacheKey, $articles, 60);
            return $this->sendSuccess(
                'Personalized news feed fetched successfully',
                $articles
            );
        } catch (QueryException $e) {
            return $this->sendError(
                'Database error',
                ['error' => $e->getMessage()],
                500
            );
        } catch (\Exception $e) {
            return $this->sendError(
                'Something went wrong',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}
