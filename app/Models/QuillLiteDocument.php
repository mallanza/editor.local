<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuillLiteDocument extends Model
{
    protected $fillable = [
        'delta',
        'changes',
        'comments',
        'text',
        'html',
    ];

    protected $casts = [
        'delta' => 'array',
        'changes' => 'array',
        'comments' => 'array',
        'html' => 'string',
    ];
}
