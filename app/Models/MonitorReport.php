<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonitorReport extends Model
{
    use SoftDeletes;

    public const SEVERITIES = ['low', 'medium', 'high', 'critical'];
    public const RESOLUTION_STATUSES = ['pending', 'resolved', 'escalated'];

    protected $fillable = [
        'employee_id', 'department_id', 'client_id', 'page_id', 'reporter_employee_id',
        'review_date', 'issue_type', 'description', 'severity', 'recommendation',
        'resolution_status', 'screenshot_path', 'status', 'verified_by', 'verified_at',
        'approved_by', 'approved_at', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'review_date' => 'date',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function employee() { return $this->belongsTo(Employee::class); }
    public function reporter() { return $this->belongsTo(Employee::class, 'reporter_employee_id'); }
    public function department() { return $this->belongsTo(Department::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function page() { return $this->belongsTo(ClientPage::class, 'page_id'); }
}
