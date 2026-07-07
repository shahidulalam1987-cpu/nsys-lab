<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagedDocumentVersion extends Model
{
    protected $fillable = [
        'managed_document_id',
        'version',
        'file_path',
        'original_file_name',
        'file_type',
        'file_size',
        'uploaded_by',
        'change_note',
    ];

    public function document()
    {
        return $this->belongsTo(ManagedDocument::class, 'managed_document_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
