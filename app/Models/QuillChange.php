<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuillChange extends Model
{
    public const TYPE_INSERT = 'insert';
    public const TYPE_DELETE = 'delete';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'document_id',
        'change_uuid',
        'user_id',
        'user_name',
        'change_type',
        'status',
        'anchor_index',
        'anchor_length',
        'delta',
    ];

    protected $casts = [
        'delta' => 'array',
        'anchor_index' => 'integer',
        'anchor_length' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(QuillDocument::class, 'document_id');
    }
}
