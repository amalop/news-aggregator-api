<?php
namespace App\Http\Controllers\Api;

use App\Models\Article;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class ArticleController extends BaseApiController
{
    public function index(Request $request)
    {
        try {
            $query = Article::query();

            // Filter by keyword
            if ($request->has('keyword')) {
                $query->where('title', 'like', '%' . $request->keyword . '%');
            }

            // Filter by date
            if ($request->has('date')) {
                $query->whereDate('published_at', $request->date);
            }

            // Filter by category
            if ($request->has('category')) {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('name', $request->category);
                });
            }

            // Filter by source
            if ($request->has('source')) {
                $query->whereHas('source', function ($q) use ($request) {
                    $q->where('name', $request->source);
                });
            }

            // Paginate results
            $articles = $query->with(['category', 'source', 'author'])->paginate(10);

            return $this->sendSuccess('Articles fetched successfully', $articles);
        } catch (QueryException $e) {
            return $this->sendError('Database error', ['error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $article = Article::with(['category', 'source', 'author'])->find($id);

            if (!$article) {
                return $this->sendError('Article not found', [], 404);
            }

            return $this->sendSuccess('Article fetched successfully', $article);
        } catch (QueryException $e) {
            return $this->sendError('Database error', ['error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }
}
