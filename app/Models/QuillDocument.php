<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuillDocument extends Model
{
    protected $fillable = [
        'title',
        'content_delta',
        'clean_delta',
        'base_delta',
        'version',
    ];

    protected $casts = [
        'content_delta' => 'array',
        'clean_delta' => 'array',
        'base_delta' => 'array',
    ];

    public function comments(): HasMany
    {
        return $this->hasMany(QuillComment::class, 'document_id');
    }

    public function changes(): HasMany
    {
        return $this->hasMany(QuillChange::class, 'document_id');
    }
}
