<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyExpense extends Model
{
    public const CATEGORIES = [
        'family_support' => 'Family Support',
        'medical' => 'Medical',
        'education' => 'Education',
        'household' => 'Household',
        'rent' => 'Rent',
        'gift' => 'Gift',
        'emergency' => 'Emergency',
        'other' => 'Other',
    ];

    protected $fillable = [
        'expense_date',
        'person_name',
        'relation',
        'expense_category',
        'amount',
        'payment_method',
        'finance_account_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function account()
    {
        return $this->belongsTo(FinanceAccount::class, 'finance_account_id');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->expense_category] ?? ucwords(str_replace('_', ' ', (string) $this->expense_category));
    }
}
