<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MisoAccomplishment extends Model
{
    use HasFactory, SoftDeletes;

    public const CATEGORY_DATA_MANAGEMENT = 'data_management';
    public const CATEGORY_NETWORK = 'network';
    public const CATEGORY_SYSTEMS_DEVELOPMENT = 'systems_development';

    protected $fillable = [
        'category',
        'source_file',
        'source_row',
        'source_hash',
        'record_no',
        'project_title',
        'project_lead',
        'project_members',
        'budget_cost',
        'implementing_unit',
        'target_activities',
        'intended_duration',
        'start_date',
        'target_end_date',
        'reporting_period',
        'completion_percentage',
        'overall_status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'source_row' => 'integer',
        ];
    }

    public static function categoryOptions(): array
    {
        return [
            self::CATEGORY_DATA_MANAGEMENT => 'MISO Accomplishments Data',
            self::CATEGORY_NETWORK => 'Network / Cybersec / Tech Support',
            self::CATEGORY_SYSTEMS_DEVELOPMENT => 'Systems Development / QA',
        ];
    }
}
