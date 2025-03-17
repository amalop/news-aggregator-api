<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'article_identifier',
        'description',
        'content',
        'category_id',
        'source_id',
        'author_id',
        'published_at',
    ];

    // Relationship with Category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relationship with Source
    public function source()
    {
        return $this->belongsTo(Source::class);
    }

    // Relationship with Author
    public function author()
    {
        return $this->belongsTo(Author::class);
    }
}
