<?php

namespace App\Services;

use App\Models\MarketingOperationSetting;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class MarketingOperationsSettingsService
{
    public const CACHE_KEY = 'marketing_operations_settings';

    public const DEFAULTS = [
        'timezone' => 'Asia/Dhaka',
        'moderator_submission_start' => '01:00',
        'moderator_submission_end' => '02:00',
        'ad_manager_submission_start' => '01:00',
        'ad_manager_submission_end' => '02:00',
        'auditor_review_start' => '02:00',
        'auditor_review_end' => '08:00',
        'monitor_review_start' => '08:00',
        'monitor_review_end' => '11:00',
        'agency_review_start' => '11:00',
        'agency_review_end' => '13:00',
        'late_submission_buffer_minutes' => '1',
        'missing_report_buffer_minutes' => '30',
        'reminder_before_open_minutes' => '10',
        'reminder_before_close_minutes' => '15,5',
    ];

    public const LABELS = [
        'timezone' => 'Timezone',
        'moderator_submission_start' => 'Moderator Submission Start',
        'moderator_submission_end' => 'Moderator Submission End',
        'ad_manager_submission_start' => 'Ad Manager Submission Start',
        'ad_manager_submission_end' => 'Ad Manager Submission End',
        'auditor_review_start' => 'Auditor Review Start',
        'auditor_review_end' => 'Auditor Review End',
        'monitor_review_start' => 'Monitor Review Start',
        'monitor_review_end' => 'Monitor Review End',
        'agency_review_start' => 'Agency Review Start',
        'agency_review_end' => 'Agency Review End',
        'late_submission_buffer_minutes' => 'Late Submission Buffer',
        'missing_report_buffer_minutes' => 'Missing Report Buffer',
        'reminder_before_open_minutes' => 'Reminder Before Open',
        'reminder_before_close_minutes' => 'Reminder Before Close',
    ];

    public function all(): array
    {
        $settings = $this->storedSettings();

        return collect(self::DEFAULTS)
            ->mapWithKeys(fn ($default, $key) => [$key => $settings[$key] ?? $default])
            ->all();
    }

    public function get(string $key): string
    {
        return $this->all()[$key] ?? self::DEFAULTS[$key] ?? '';
    }

    public function update(array $settings, ?User $user = null): void
    {
        if (! Schema::hasTable('marketing_operation_settings')) {
            return;
        }

        foreach (array_intersect_key($settings, self::DEFAULTS) as $key => $value) {
            MarketingOperationSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => (string) $value,
                    'type' => $this->typeFor($key),
                    'description' => self::LABELS[$key] ?? $key,
                    'updated_by' => $user?->id,
                ]
            );
        }

        Cache::forget(self::CACHE_KEY);
    }

    public function windowFor(string $module, ?CarbonInterface $date = null): array
    {
        $date = Carbon::parse($date ?: now(), $this->timezone());
        $prefix = match ($module) {
            'moderator' => 'moderator_submission',
            'ad-manager' => 'ad_manager_submission',
            'auditor' => 'auditor_review',
            'monitor' => 'monitor_review',
            'agency' => 'agency_review',
            default => 'moderator_submission',
        };

        return [
            'start' => $this->timeOnDate($date, $this->get($prefix . '_start')),
            'end' => $this->timeOnDate($date, $this->get($prefix . '_end')),
        ];
    }

    public function statusForSubmission(string $module, ?CarbonInterface $now = null): string
    {
        $now = Carbon::parse($now ?: now(), $this->timezone());
        $window = $this->windowFor($module, $now);

        if ($now->lt($window['start'])) {
            return 'draft';
        }

        if ($now->lte($window['end'])) {
            return 'submitted';
        }

        return 'late_submitted';
    }

    public function missingReportCutoff(string $module, ?CarbonInterface $date = null): CarbonInterface
    {
        $window = $this->windowFor($module, $date);

        return $window['end']->copy()->addMinutes($this->integer('missing_report_buffer_minutes'));
    }

    public function reminderSchedule(string $module, ?CarbonInterface $date = null): array
    {
        $window = $this->windowFor($module, $date);
        $beforeOpen = $this->integer('reminder_before_open_minutes');
        $beforeClose = collect(explode(',', $this->get('reminder_before_close_minutes')))
            ->map(fn ($value) => (int) trim($value))
            ->filter(fn ($value) => $value > 0)
            ->values();

        return [
            'opens_soon' => $window['start']->copy()->subMinutes($beforeOpen),
            'closing_soon' => $beforeClose->map(fn ($minutes) => $window['end']->copy()->subMinutes($minutes))->all(),
            'deadline' => $window['end'],
            'late' => $window['end']->copy()->addMinutes($this->integer('late_submission_buffer_minutes')),
            'missing' => $this->missingReportCutoff($module, $date),
        ];
    }

    public function timezone(): string
    {
        return $this->get('timezone') ?: 'Asia/Dhaka';
    }

    public function integer(string $key): int
    {
        return max(0, (int) $this->get($key));
    }

    private function storedSettings(): array
    {
        if (! Schema::hasTable('marketing_operation_settings')) {
            return [];
        }

        return Cache::remember(self::CACHE_KEY, 300, fn () => MarketingOperationSetting::query()
            ->pluck('value', 'key')
            ->all());
    }

    private function timeOnDate(CarbonInterface $date, string $time): CarbonInterface
    {
        [$hour, $minute] = array_pad(explode(':', $time), 2, 0);

        return Carbon::parse($date, $this->timezone())->setTime((int) $hour, (int) $minute);
    }

    private function typeFor(string $key): string
    {
        if (str_contains($key, '_minutes')) {
            return 'integer';
        }

        if (str_contains($key, '_start') || str_contains($key, '_end')) {
            return 'time';
        }

        return $key === 'reminder_before_close_minutes' ? 'csv' : 'string';
    }
}
