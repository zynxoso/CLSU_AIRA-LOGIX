<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IctSearchIndex extends Model
{
    protected $table = 'ict_search_indexes';
    protected $fillable = ['ict_service_request_id', 'hash'];

    public function serviceRequest()
    {
        return $this->belongsTo(IctServiceRequest::class, 'ict_service_request_id');
    }
}
