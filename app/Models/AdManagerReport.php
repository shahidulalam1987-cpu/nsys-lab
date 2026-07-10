<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdManagerReport extends Model
{
    use SoftDeletes;

    public const STATUSES = ['draft', 'submitted', 'verified', 'rejected', 'approved'];

    protected $fillable = [
        'client_id', 'page_id', 'campaign_id', 'employee_id', 'report_date',
        'spend_usd', 'spend_bdt', 'purchases', 'cpp', 'cpm', 'ctr', 'cpc',
        'frequency', 'reach', 'impressions', 'notes', 'attachment_path',
        'status', 'verified_by', 'verified_at', 'approved_by', 'approved_at',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function client() { return $this->belongsTo(Client::class); }
    public function page() { return $this->belongsTo(ClientPage::class, 'page_id'); }
    public function campaign() { return $this->belongsTo(Campaign::class); }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    public static function costPer(float $spend, int $orders): float
    {
        return $orders > 0 ? round($spend / $orders, 2) : 0.0;
    }

    public function statusLabel(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }
}
