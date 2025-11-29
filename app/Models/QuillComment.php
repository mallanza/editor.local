<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuillComment extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'document_id',
        'user_id',
        'user_name',
        'anchor_index',
        'anchor_length',
        'body',
        'status',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(QuillDocument::class, 'document_id');
    }
}
