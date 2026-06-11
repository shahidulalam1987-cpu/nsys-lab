<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessManager extends Model
{
    public const VERIFICATION_STATUSES = [
        'verified' => 'Verified',
        'unverified' => 'Unverified',
        'pending_review' => 'Pending Review',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'restricted' => 'Restricted',
        'disabled' => 'Disabled',
    ];

    protected $fillable = [
        'bm_name',
        'bm_id',
        'owner_name',
        'owner_email',
        'verification_status',
        'status',
        'notes',
    ];

    public function adAccounts()
    {
        return $this->hasMany(AdAccount::class);
    }

    public function pages()
    {
        return $this->hasMany(ClientPage::class);
    }

    public function verificationStatusLabel(): string
    {
        return self::VERIFICATION_STATUSES[$this->verification_status] ?? ucwords(str_replace('_', ' ', $this->verification_status));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucwords(str_replace('_', ' ', $this->status));
    }
}
