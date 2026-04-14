<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPost extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'slug',
        'content',
        'image',
        'image_prompt',
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
