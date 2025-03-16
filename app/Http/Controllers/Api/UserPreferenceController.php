<?php
namespace App\Http\Controllers\Api;

use App\Models\UserPreference;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class UserPreferenceController extends BaseApiController
{
    public function update(Request $request)
    {
        try {
            $request->validate([
                'preferred_categories' => 'nullable|array',
                'preferred_sources' => 'nullable|array',
                'preferred_authors' => 'nullable|array',
            ]);

            $user = $request->user();
            $preferences = $user->preferences ?? new UserPreference();
            $preferences->preferred_categories = $request->preferred_categories;
            $preferences->preferred_sources = $request->preferred_sources;
            $preferences->preferred_authors = $request->preferred_authors;
            $user->preferences()->save($preferences);

            return $this->sendSuccess('Preferences updated successfully', $preferences);
        } catch (ValidationException $e) {
            return $this->sendError('Validation error', $e->errors(), 422);
        } catch (QueryException $e) {
            return $this->sendError('Database error', ['error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            $preferences = $request->user()->preferences;

            if (!$preferences) {
                return $this->sendSuccess('No preferences found', []);
            }

            return $this->sendSuccess('Preferences fetched successfully', $preferences);
        } catch (QueryException $e) {
            return $this->sendError('Database error', ['error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    public function personalizedFeed(Request $request)
    {
        try {
            $user = $request->user();
            $preferences = $user->preferences;

            if (!$preferences) {
                return $this->sendSuccess('No preferences set', []);
            }

            $query = Article::query();

            // Filter by preferred categories
            if ($preferences->preferred_categories) {
                $query->whereIn('category_id', $preferences->preferred_categories);
            }

            // Filter by preferred sources
            if ($preferences->preferred_sources) {
                $query->whereIn('source_id', $preferences->preferred_sources);
            }

            // Filter by preferred authors
            if ($preferences->preferred_authors) {
                $query->whereIn('author_id', $preferences->preferred_authors);
            }

            // Paginate results
            $articles = $query->with(['category', 'source', 'author'])->paginate(10);

            return $this->sendSuccess('Personalized feed fetched successfully', $articles);
        } catch (QueryException $e) {
            return $this->sendError('Database error', ['error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }
}
