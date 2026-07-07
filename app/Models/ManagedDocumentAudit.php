<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagedDocumentAudit extends Model
{
    protected $fillable = [
        'managed_document_id',
        'user_id',
        'action',
        'description',
        'ip_address',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function document()
    {
        return $this->belongsTo(ManagedDocument::class, 'managed_document_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
