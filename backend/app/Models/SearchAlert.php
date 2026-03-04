<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchAlert extends Model
{
    protected $fillable = [
        'email',
        'type',
        'city',
        'region',
        'filters',
        'unsubscribe_token',
        'last_notified_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'last_notified_at' => 'datetime',
    ];
}
