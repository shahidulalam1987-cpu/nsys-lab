<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfitReport extends Model
{
    protected $fillable = [
        'report_month',
        'report_type',
        'reference_id',
        'spend_usd',
        'fee_usd',
        'total_deducted_usd',
        'bdt_cost',
        'client_revenue',
        'net_profit',
    ];

    protected function casts(): array
    {
        return [
            'report_month' => 'date',
            'spend_usd' => 'decimal:2',
            'fee_usd' => 'decimal:2',
            'total_deducted_usd' => 'decimal:2',
            'bdt_cost' => 'decimal:2',
            'client_revenue' => 'decimal:2',
            'net_profit' => 'decimal:2',
        ];
    }
}
