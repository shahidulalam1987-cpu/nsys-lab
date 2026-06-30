<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceVerification extends Model
{
    protected $fillable = ['group_key', 'performance_date', 'client_id', 'page_id', 'campaign_id', 'status', 'admin_note', 'marked_by'];

    protected function casts(): array
    {
        return ['performance_date' => 'date'];
    }
}
