<?php

namespace App\Http\Controllers\Api;

use App\Models\Article;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Article",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Laravel 10 Released"),
 *     @OA\Property(property="content", type="string", example="Laravel 10 is now available with new features..."),
 *     @OA\Property(property="published_at", type="string", format="date-time", example="2023-10-01T12:00:00Z"),
 *     @OA\Property(
 *         property="category",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Technology")
 *     ),
 *     @OA\Property(
 *         property="source",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="BBC News")
 *     ),
 *     @OA\Property(
 *         property="author",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="John Doe")
 *     )
 * )
 */

class ArticleController extends BaseApiController
{
    /**
     * Get a list of articles.
     *
     * @OA\Get(
     *     path="/api/articles",
     *     summary="Get a list of articles",
     *     tags={"Articles"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="keyword",
     *         in="query",
     *         description="Keyword to search in article titles",
     *         @OA\Schema(type="string", example="Laravel")
     *     ),
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Filter articles by published date (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date", example="2023-10-01")
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter articles by category name",
     *         @OA\Schema(type="string", example="Technology")
     *     ),
     *     @OA\Parameter(
     *         name="source",
     *         in="query",
     *         description="Filter articles by source name",
     *         @OA\Schema(type="string", example="BBC News")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of articles",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Article")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function index(Request $request)
    {
        // Check if the user has permission to view articles
        if (!$request->user()->can('articles.view')) {
            return $this->sendError('Unauthorized', [], 403);
        }
        // Generate a unique cache key based on the request parameters
        $cacheKey = 'articles_' . md5(serialize($request->all()));

        // Check if the data is already cached
        if (Cache::has($cacheKey)) {
            // Return cached data
            $articles = Cache::get($cacheKey);
            return $this->sendSuccess(
                'Articles fetched successfully (cached)',
                $articles
            );
        }

        // Validate query parameters
        $validator = Validator::make($request->all(), [
            'keyword' => 'nullable|string|max:255',
            'date' => 'nullable|date_format:Y-m-d',
            'category' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->sendError(
                'Validation error',
                $validator->errors(),
                422
            );
        }

        // Use validated data
        $validatedData = $validator->validated();

        // Build the query
        $query = Article::query();

        if (!empty($validatedData['keyword'])) {
            $query->where(
                'title',
                'like',
                '%' . $validatedData['keyword'] . '%'
            );
        }
        if (!empty($validatedData['date'])) {
            $query->whereDate('published_at', $validatedData['date']);
        }
        if (!empty($validatedData['category'])) {
            $query->whereHas(
                'category',
                fn($q) => $q->where('name', $validatedData['category'])
            );
        }
        if (!empty($validatedData['source'])) {
            $query->whereHas(
                'source',
                fn($q) => $q->where('name', $validatedData['source'])
            );
        }

        // Fetch articles with relationships
        $articles = $query
            ->with(['category', 'source', 'author'])
            ->paginate(10);

        // Cache the results for 60 minutes
        Cache::put($cacheKey, $articles, 60);

        return $this->sendSuccess('Articles fetched successfully', $articles);
    }

    /**
     * Get a single article.
     *
     * @OA\Get(
     *     path="/api/articles/{id}",
     *     summary="Get a single article",
     *     tags={"Articles"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Article ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Article details",
     *         @OA\JsonContent(ref="#/components/schemas/Article")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Article not found"
     *     )
     * )
     */
    public function show(Request $request, $id)
    {
        // Check if the user has permission to view article
        if (!$request->user()->can('articles.view')) {
            return $this->sendError('Unauthorized', [], 403);
        }

        // Generate a unique cache key for the article
        $cacheKey = 'article_' . $id;

        // Check if the data is already cached
        if (Cache::has($cacheKey)) {
            // Return cached data
            $article = Cache::get($cacheKey);
            return $this->sendSuccess(
                'Article fetched successfully (cached)',
                $article
            );
        }

        // Fetch the article
        $article = Article::with(['category', 'source', 'author'])->find($id);

        if (!$article) {
            return $this->sendError('Article not found', [], 404);
        }
        // Cache the article for 60 minutes
        Cache::put($cacheKey, $article, 60);

        return $this->sendSuccess('Article fetched successfully', $article);
    }
}
