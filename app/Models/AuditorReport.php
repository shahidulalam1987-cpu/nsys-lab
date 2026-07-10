<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditorReport extends Model
{
    use SoftDeletes;

    public const OVERALL_STATUSES = ['excellent', 'good', 'average', 'poor', 'critical'];

    protected $fillable = [
        'client_id', 'page_id', 'moderator_id', 'employee_id', 'audit_date',
        'average_response_time', 'longest_delay', 'total_delayed_replies',
        'qa_score', 'message_quality', 'greeting_score', 'closing_score',
        'follow_up_score', 'remarks', 'screenshot_path', 'overall_status',
        'status', 'verified_by', 'verified_at', 'approved_by', 'approved_at',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'audit_date' => 'date',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function client() { return $this->belongsTo(Client::class); }
    public function page() { return $this->belongsTo(ClientPage::class, 'page_id'); }
    public function moderator() { return $this->belongsTo(Employee::class, 'moderator_id'); }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
