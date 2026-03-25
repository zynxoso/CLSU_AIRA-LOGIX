<?php

namespace App\Models;

use App\Casts\TolerantEncrypted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class IctServiceRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'control_no',
        'timestamp_str',
        'client_feedback_no',
        'name',
        'name_index',
        'position',
        'office_unit',
        'contact_no',
        'date_of_request',
        'requested_completion_date',
        'request_type',
        'location_venue',
        'request_description',
        'received_by',
        'receive_date_time',
        'action_taken',
        'recommendation_conclusion',
        'status',
        'date_time_started',
        'date_time_completed',
        'conducted_by',
        'noted_by',
    ];

    protected function casts(): array
    {
        return [
            'name' => TolerantEncrypted::class,
            'position' => TolerantEncrypted::class,
            'office_unit' => TolerantEncrypted::class,
            'contact_no' => TolerantEncrypted::class,
            'location_venue' => TolerantEncrypted::class,
            'request_description' => TolerantEncrypted::class,
            'received_by' => TolerantEncrypted::class,
            'action_taken' => TolerantEncrypted::class,
            'recommendation_conclusion' => TolerantEncrypted::class,
            'conducted_by' => TolerantEncrypted::class,
            'noted_by' => TolerantEncrypted::class,

            'date_of_request' => 'datetime',
            'requested_completion_date' => 'datetime',
            'receive_date_time' => 'datetime',
            'date_time_started' => 'datetime',
            'date_time_completed' => 'datetime',
        ];
    }

    public function searchIndexes()
    {
        return $this->hasMany(IctSearchIndex::class);
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            if ($model->isDirty('name')) {
                $model->name_index = self::generateNameIndex($model->name);
            }
        });

        static::saved(function ($model) {
            if ($model->wasChanged('name')) {
                $model->updateSearchIndexes();
            }
        });
    }

    public function updateSearchIndexes()
    {
        $this->searchIndexes()->delete();
        
        if (empty($this->name)) return;

        // Split name into words (e.g., "Julieta D. Holasca" -> ["Julieta", "D.", "Holasca"])
        $words = preg_split('/\s+/', $this->name, -1, PREG_SPLIT_NO_EMPTY);
        
        $hashes = array_unique(array_map(fn($word) => self::generateNameIndex($word), $words));

        foreach ($hashes as $hash) {
            $this->searchIndexes()->create(['hash' => $hash]);
        }
    }

    public static function generateNameIndex($value)
    {
        if (empty($value)) return null;
        
        // Normalize: lowercase, trim
        $normalized = strtolower(trim($value));
        
        return hash_hmac('sha256', $normalized, config('app.key'));
    }
}
