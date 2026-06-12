<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundingBalanceHistory extends Model
{
    protected $table = 'funding_balance_history';

    protected $fillable = [
        'funding_balance_id',
        'source',
        'previous_balance',
        'new_balance',
        'difference',
        'currency',
        'balance_date',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'previous_balance' => 'decimal:2',
            'new_balance' => 'decimal:2',
            'difference' => 'decimal:2',
            'balance_date' => 'date',
        ];
    }

    public function fundingBalance()
    {
        return $this->belongsTo(FundingBalance::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sourceLabel(): string
    {
        return FundingBalance::SOURCES[$this->source] ?? ucwords(str_replace('_', ' ', (string) $this->source));
    }
}
