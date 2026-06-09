<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BugReport extends Model
{
    public const PRIORITIES = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
    ];

    public const STATUSES = [
        'open' => 'Open',
        'in_progress' => 'In Progress',
        'fixed' => 'Fixed',
        'closed' => 'Closed',
    ];

    protected $fillable = [
        'bug_id',
        'module',
        'title',
        'description',
        'priority',
        'status',
        'reported_by',
        'assigned_to',
        'fixed_note',
    ];

    public function priorityLabel(): string
    {
        return self::PRIORITIES[$this->priority] ?? ucwords(str_replace('_', ' ', $this->priority));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucwords(str_replace('_', ' ', $this->status));
    }
}
