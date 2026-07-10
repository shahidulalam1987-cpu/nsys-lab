<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgencyOperationReview extends Model
{
    protected $fillable = [
        'review_date', 'today_orders', 'today_spend', 'today_revenue',
        'today_estimated_profit', 'pending_reports', 'pending_verifications',
        'alerts', 'final_status', 'verified_by', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'review_date' => 'date',
            'approved_at' => 'datetime',
            'alerts' => 'array',
        ];
    }
}
