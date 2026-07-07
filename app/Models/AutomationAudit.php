<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationAudit extends Model
{
    protected $fillable = [
        'automation_task_id',
        'triggered_by',
        'rule_key',
        'event_name',
        'result',
        'description',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function task()
    {
        return $this->belongsTo(AutomationTask::class, 'automation_task_id');
    }

    public function triggeredBy()
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
