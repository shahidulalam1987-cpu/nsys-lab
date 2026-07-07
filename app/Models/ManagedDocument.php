<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ManagedDocument extends Model
{
    public const CATEGORIES = [
        'Employee',
        'Client',
        'Finance',
        'Payroll',
        'Legal',
        'Facebook Assets',
        'Contracts',
        'HR',
        'Invoices',
        'Receipts',
        'General',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'archived' => 'Archived',
    ];

    public const OWNER_MODULES = [
        'employee' => 'Employee',
        'client' => 'Client',
        'payroll' => 'Payroll',
        'assignment' => 'Assignment',
        'finance_account' => 'Finance Account',
        'client_payment' => 'Client Payment',
        'employee_payment' => 'Employee Payment',
        'daily_performance' => 'Daily Performance',
        'automation_task' => 'Automation Task',
    ];

    protected $fillable = [
        'title',
        'description',
        'category',
        'tags',
        'owner_module',
        'owner_record_type',
        'owner_record_id',
        'uploaded_by',
        'uploaded_at',
        'file_type',
        'file_size',
        'version',
        'status',
        'expiry_date',
        'current_file_path',
        'original_file_name',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'uploaded_at' => 'datetime',
            'expiry_date' => 'date',
        ];
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function owner()
    {
        return $this->morphTo(__FUNCTION__, 'owner_record_type', 'owner_record_id');
    }

    public function versions()
    {
        return $this->hasMany(ManagedDocumentVersion::class)->orderByDesc('version');
    }

    public function audits()
    {
        return $this->hasMany(ManagedDocumentAudit::class)->latest();
    }

    public function categoryLabel(): string
    {
        return in_array($this->category, self::CATEGORIES, true) ? $this->category : 'General';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function fileSizeLabel(): string
    {
        $size = (int) $this->file_size;

        if ($size >= 1048576) {
            return number_format($size / 1048576, 2) . ' MB';
        }

        return number_format(max($size, 0) / 1024, 1) . ' KB';
    }

    public function fileUrl(): string
    {
        return Storage::url($this->current_file_path);
    }
}
