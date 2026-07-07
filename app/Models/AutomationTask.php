<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationTask extends Model
{
    public const PRIORITIES = [
        'critical' => 'Critical',
        'high' => 'High',
        'medium' => 'Medium',
        'low' => 'Low',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'completed' => 'Completed',
        'dismissed' => 'Dismissed',
    ];

    protected $fillable = [
        'task_key',
        'title',
        'priority',
        'status',
        'department',
        'assigned_user_id',
        'related_module',
        'related_record_type',
        'related_record_id',
        'due_date',
        'completed_at',
        'completed_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function audits()
    {
        return $this->hasMany(AutomationAudit::class);
    }

    public function priorityLabel(): string
    {
        return self::PRIORITIES[$this->priority] ?? ucwords(str_replace('_', ' ', (string) $this->priority));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucwords(str_replace('_', ' ', (string) $this->status));
    }

    public function priorityBadgeClass(): string
    {
        return [
            'critical' => 'badge-danger',
            'high' => 'badge-danger',
            'medium' => 'badge-warning',
            'low' => 'badge-info',
        ][$this->priority] ?? 'badge-neutral';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending'
            && $this->due_date
            && $this->due_date->lt(today());
    }
}
