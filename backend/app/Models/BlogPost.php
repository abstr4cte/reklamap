<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPost extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'image',
        'user_id',
        'published_at',
        'category',
    ];

    protected $attributes = [
        'status' => 'published',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getExcerptAttribute(): string
    {
        // Usuń HTML tagi i pobierz pierwsze 150 znaków
        $text = strip_tags($this->content);
        return substr($text, 0, 150) . (strlen($text) > 150 ? '...' : '');
    }
}
