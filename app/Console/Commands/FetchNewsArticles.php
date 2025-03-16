<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use App\Models\Author;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Client\HttpException;

class FetchNewsArticles extends Command
{
    protected $signature = 'fetch:news-articles';
    protected $description = 'Fetch articles from external news APIs and store them in the database.';

    public function handle()
    {
        try {
            $this->fetchAndStoreArticles('NewsAPI', [
                'url' =>
                    'https://newsapi.org/v2/top-headlines?country=us&apiKey=' .
                    config('services.newsapi.key'),
                'title_key' => 'title',
                'desc_key' => 'description',
                'content_key' => 'content',
                'author_key' => 'author',
                'category_key' => 'category',
                'date_key' => 'publishedAt',
                'data_path' => 'articles',
            ]);

            $this->fetchAndStoreArticles('The Guardian', [
                'url' =>
                    'https://content.guardianapis.com/search?api-key=' .
                    config('services.theguardian.key'),
                'title_key' => 'webTitle',
                'desc_key' => 'webTitle',
                'content_key' => 'webTitle',
                'author_key' => null,
                'category_key' => 'pillarName',
                'date_key' => 'webPublicationDate',
                'data_path' => 'response.results',
            ]);

            $this->fetchAndStoreArticles('New York Times', [
                'url' =>
                    'https://api.nytimes.com/svc/topstories/v2/home.json?api-key=' .
                    config('services.nytimes.key'),
                'title_key' => 'title',
                'desc_key' => 'abstract',
                'content_key' => 'abstract',
                'author_key' => 'byline',
                'category_key' => 'section',
                'date_key' => 'published_date',
                'data_path' => 'results',
            ]);

            $this->info('Articles fetched and stored successfully.');
        } catch (\Exception $e) {
            Log::error('Error in FetchNewsArticles: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('An error occurred: ' . $e->getMessage());
        }
    }

    protected function fetchAndStoreArticles($sourceName, $config)
    {
        $response = $this->fetchArticles($config['url']);

        if ($response) {
            $source = Source::firstOrCreate(['name' => $sourceName]);

            $articles = data_get($response, $config['data_path'], []);
            $articlesToStore = [];
            foreach ($articles as $article) {
                $articlesToStore[] = [
                    'title' => $article[$config['title_key']] ?? 'No Title',
                    'description' => $article[$config['desc_key']] ?? '',
                    'content' => $article[$config['content_key']] ?? '',
                    'author' => $article[$config['author_key']] ?? 'Unknown',
                    'category' =>
                        $article[$config['category_key']] ?? 'General',
                    'published_at' => Carbon::parse(
                        $article[$config['date_key']] ?? now()
                    ),
                    'source' => $source->id,
                ];
            }
            $this->storeArticles($articlesToStore);
        }
    }

    protected function fetchArticles($url)
    {
        try {
            return Http::retry(3, 100)
                ->get($url)
                ->throw()
                ->json();
        } catch (\Exception $e) {
            Log::error(
                "Failed to fetch articles from {$url}: " . $e->getMessage()
            );
            return null;
        }
    }

    protected function storeArticles($articles)
    {
        if (empty($articles)) {
            return;
        }

        $now = now();

        $categories = collect($articles)
            ->pluck('category')
            ->unique()
            ->mapWithKeys(function ($category) {
                return [
                    $category => Category::firstOrCreate(['name' => $category])
                        ->id,
                ];
            });

        $authors = collect($articles)
            ->pluck('author')
            ->unique()
            ->mapWithKeys(function ($author) {
                return [
                    $author => Author::firstOrCreate(['name' => $author])->id,
                ];
            });

        $articlesToInsert = collect($articles)
            ->map(function ($article) use ($categories, $authors, $now) {
                // Generate a unique identifier
                $articleIndentifier = md5(
                    $article['title'] .
                        $article['source'] .
                        $article['published_at']
                );
                return [
                    'article_identifier' => $articleIndentifier,
                    'title' => $article['title'],
                    'description' => $article['description'],
                    'content' => $article['content'],
                    'category_id' => $categories[$article['category']],
                    'source_id' => $article['source'],
                    'author_id' => $authors[$article['author']],
                    'published_at' => $article['published_at'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->toArray();
            Article::upsert(
                $articlesToInsert, // Data to insert/update
                ['article_identifier'], // Unique identifier column
                [
                    'title',
                    'description',
                    'content',
                    'category_id',
                    'source_id',
                    'author_id',
                    'published_at',
                    'updated_at',
                ] // Columns to update if a conflict occurs
            );
    }
}
