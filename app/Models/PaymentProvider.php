<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentProvider extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'provider_code',
        'name',
        'provider_type',
        'currency',
        'status',
        'notes',
    ];

    public function transactions()
    {
        return $this->hasMany(ProviderTransaction::class);
    }
}
