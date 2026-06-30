<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDailySubmission extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'order' => 'Order',
        'spend' => 'Spend',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'merged' => 'Merged',
    ];

    protected $fillable = [
        'employee_id',
        'client_id',
        'page_id',
        'campaign_id',
        'bm_id',
        'ad_account_id',
        'submission_date',
        'submission_type',
        'orders',
        'confirmed_orders',
        'cancelled_orders',
        'dollar_spend',
        'cpm',
        'cpc',
        'ctr',
        'screenshot_path',
        'note',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_note',
        'submission_key',
    ];

    protected function casts(): array
    {
        return [
            'submission_date' => 'date',
            'dollar_spend' => 'decimal:2',
            'cpm' => 'decimal:2',
            'cpc' => 'decimal:2',
            'ctr' => 'decimal:4',
            'reviewed_at' => 'datetime',
        ];
    }

    public static function duplicateKey(int $employeeId, string $date, string $type, ?int $pageId, ?int $campaignId): string
    {
        return implode(':', [$employeeId, $date, $type, $pageId ?: 0, $campaignId ?: 0]);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
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

    public function businessManager()
    {
        return $this->belongsTo(BusinessManager::class, 'bm_id');
    }

    public function adAccount()
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}
