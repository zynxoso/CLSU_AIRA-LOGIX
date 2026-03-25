<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AiUsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'service',
        'model',
        'user_id',
        'extraction_method',
        'source_file_type',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'estimated_cost',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'encrypted:array',
        'estimated_cost' => 'decimal:6',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
