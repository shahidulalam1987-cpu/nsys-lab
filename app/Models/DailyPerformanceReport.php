<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyPerformanceReport extends Model
{
    protected $fillable = [
        'campaign_id',
        'report_date',
        'spend',
        'messages',
        'results',
        'leads',
        'orders',
        'reach',
        'impressions',
        'clicks',
        'cpm',
        'cpr',
        'cpl',
        'cpp',
        'cpc',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'spend' => 'decimal:2',
            'cpm' => 'decimal:2',
            'cpr' => 'decimal:2',
            'cpl' => 'decimal:2',
            'cpp' => 'decimal:2',
            'cpc' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (DailyPerformanceReport $report) {
            $spend = (float) $report->spend;
            $report->cpm = self::costPer($spend, (int) $report->messages);
            $report->cpr = self::costPer($spend, (int) $report->results);
            $report->cpl = self::costPer($spend, (int) $report->leads);
            $report->cpp = self::costPer($spend, (int) $report->orders);
            $report->cpc = self::costPer($spend, (int) $report->clicks);
        });
    }

    public static function costPer(float $spend, int $count): float
    {
        if ($spend <= 0 || $count <= 0) {
            return 0.0;
        }

        return round($spend / $count, 2);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
