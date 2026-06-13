<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemNotification extends Model
{
    public const PRIORITIES = [
        'critical' => 'Critical',
        'warning' => 'Warning',
        'information' => 'Information',
    ];

    public const STATUSES = [
        'unread' => 'Unread',
        'read' => 'Read',
        'dismissed' => 'Dismissed',
        'resolved' => 'Resolved',
    ];

    protected $fillable = [
        'notification_key',
        'type',
        'department',
        'priority',
        'message',
        'status',
        'action_url',
        'reference_type',
        'reference_id',
        'target_team',
        'resolved_at',
        'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
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
            'warning' => 'badge-warning',
            'information' => 'badge-info',
        ][$this->priority] ?? 'badge-neutral';
    }

    public function isOpen(): bool
    {
        return $this->status !== 'resolved' && $this->status !== 'dismissed';
    }
}
