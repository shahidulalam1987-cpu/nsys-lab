<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketingOperationsReport extends Model
{
    use SoftDeletes;

    public const REPORT_TYPES = [
        'moderator_order' => 'Moderator Report',
        'ad_manager_spend' => 'Ad Manager Report',
        'auditor_audit' => 'Auditor Report',
        'monitor_issue' => 'Monitor Report',
        'trainer_training' => 'Trainer Report',
        'management_review' => 'Management Report',
    ];

    public const PLATFORMS = ['Meta', 'TikTok', 'Google Ads', 'YouTube', 'Other'];

    public const STATUSES = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'needs_correction' => 'Needs Correction',
        'open' => 'Open',
        'fixed' => 'Fixed',
        'repeated' => 'Repeated',
        'closed' => 'Closed',
        'merged' => 'Merged',
    ];

    public const SEVERITIES = ['Low', 'Medium', 'High'];

    protected $fillable = [
        'report_type',
        'platform',
        'report_date',
        'employee_id',
        'target_employee_id',
        'client_id',
        'page_id',
        'campaign_id',
        'ad_account_id',
        'department_id',
        'role_id',
        'metrics',
        'notes',
        'screenshot_path',
        'attachment_path',
        'severity',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_note',
        'duplicate_key',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'reviewed_at' => 'datetime',
            'metrics' => 'array',
        ];
    }

    public static function duplicateKey(array $data): string
    {
        return match ($data['report_type']) {
            'moderator_order' => implode(':', ['moderator', $data['employee_id'] ?? 0, $data['report_date'], $data['page_id'] ?? 0]),
            'ad_manager_spend' => implode(':', ['ad_manager', $data['employee_id'] ?? 0, $data['report_date'], $data['campaign_id'] ?? 0]),
            'auditor_audit' => implode(':', ['auditor', $data['employee_id'] ?? 0, $data['report_date'], $data['target_employee_id'] ?? 0, $data['page_id'] ?? 0]),
            'monitor_issue' => implode(':', ['monitor', $data['employee_id'] ?? 0, $data['report_date'], $data['target_employee_id'] ?? 0, $data['page_id'] ?? 0, $data['metrics']['mistake_category'] ?? 'none']),
            'trainer_training' => implode(':', ['trainer', $data['employee_id'] ?? 0, $data['target_employee_id'] ?? 0, $data['report_date'], $data['metrics']['training_type'] ?? 'none']),
            'management_review' => implode(':', ['management', $data['report_date'], $data['department_id'] ?? 0]),
            default => implode(':', ['report', $data['report_type'], $data['employee_id'] ?? 0, $data['report_date']]),
        };
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function targetEmployee()
    {
        return $this->belongsTo(Employee::class, 'target_employee_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function page()
    {
        return $this->belongsTo(ClientPage::class, 'page_id');
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function adAccount()
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reportTypeLabel(): string
    {
        return self::REPORT_TYPES[$this->report_type] ?? ucwords(str_replace('_', ' ', (string) $this->report_type));
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucwords(str_replace('_', ' ', (string) $this->status));
    }
}
