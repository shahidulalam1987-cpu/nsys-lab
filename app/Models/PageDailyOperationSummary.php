<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageDailyOperationSummary extends Model
{
    protected $fillable = [
        'summary_date', 'client_id', 'page_id', 'campaign_id',
        'moderator_report_id', 'ad_manager_report_id', 'auditor_report_id', 'monitor_report_id',
        'orders', 'spend_usd', 'cpp', 'revenue', 'profit', 'final_status',
        'verified_by', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'summary_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function client() { return $this->belongsTo(Client::class); }
    public function page() { return $this->belongsTo(ClientPage::class, 'page_id'); }
    public function campaign() { return $this->belongsTo(Campaign::class); }
    public function moderatorReport() { return $this->belongsTo(ModeratorReport::class); }
    public function adManagerReport() { return $this->belongsTo(AdManagerReport::class); }
    public function auditorReport() { return $this->belongsTo(AuditorReport::class); }
    public function monitorReport() { return $this->belongsTo(MonitorReport::class); }
}
