<?php

namespace App\Services;

use App\Models\DailyPerformanceReport;
use App\Models\EmployeeDailySubmission;
use App\Models\PerformanceVerification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DailyPerformanceMergeService
{
    public function merge(EmployeeDailySubmission $submission, User $actor, bool $replace = false): DailyPerformanceReport
    {
        if ($submission->status !== 'approved') {
            throw ValidationException::withMessages(['submission' => 'Only approved submissions can be merged.']);
        }

        if (! $submission->campaign_id) {
            throw ValidationException::withMessages(['submission' => 'Assign a campaign before merging this submission.']);
        }

        return DB::transaction(function () use ($submission, $actor, $replace) {
            $group = $this->approvedGroup($submission, true);
            $order = $group->where('submission_type', 'order')->sortByDesc('id')->first();
            $spend = $group->where('submission_type', 'spend')->sortByDesc('id')->first();

            if (! $order || ! $spend) {
                throw ValidationException::withMessages(['submission' => 'Both approved order and approved spend submissions are required before merge.']);
            }

            $report = DailyPerformanceReport::where('campaign_id', $submission->campaign_id)
                ->whereDate('report_date', $submission->submission_date)
                ->lockForUpdate()
                ->first();
            $sourceIds = $group->pluck('id')->sort()->values()->all();

            if ($report && $report->source_submission_ids === $sourceIds) {
                throw ValidationException::withMessages(['submission' => 'This performance group has already been merged.']);
            }

            if ($report && ! $replace) {
                throw ValidationException::withMessages(['submission' => 'Report already exists for this campaign/date. Confirm replace to continue.']);
            }

            $report ??= new DailyPerformanceReport([
                'campaign_id' => $submission->campaign_id,
                'report_date' => $submission->submission_date,
            ]);
            $report->orders = $order->orders ?? 0;
            $report->spend = $spend->dollar_spend ?? 0;
            $report->cpm = $spend->cpm ?? 0;
            $report->cpc = $spend->cpc ?? 0;
            $report->status = 'admin_approved';
            $report->merged_by = $actor->id;
            $report->merged_at = now();
            $report->source_submission_ids = $sourceIds;
            $report->notes = trim(collect([
                $report->notes,
                'Employee submission merge approved by '.$actor->name.'.',
            ])->filter()->implode("\n"));
            $report->save();
            app(ClientAdsFundService::class)->syncPerformanceDebit($report);

            $group->each->update(['status' => 'merged']);
            $operations = app(PerformanceOperationsService::class);
            PerformanceVerification::updateOrCreate(['group_key' => $operations->groupKey($submission)], [
                'performance_date' => $submission->submission_date,
                'client_id' => $submission->client_id,
                'page_id' => $submission->page_id,
                'campaign_id' => $submission->campaign_id,
                'status' => 'merged',
                'marked_by' => $actor->id,
            ]);

            return $report;
        });
    }

    public function state(EmployeeDailySubmission $submission): array
    {
        if ($submission->status !== 'approved' || ! $submission->campaign_id) {
            return ['ready' => false, 'existing_report_id' => null];
        }

        $types = $this->approvedGroup($submission)->pluck('submission_type')->unique();
        $existing = DailyPerformanceReport::where('campaign_id', $submission->campaign_id)
            ->whereDate('report_date', $submission->submission_date)
            ->first();

        return [
            'ready' => $types->contains('order') && $types->contains('spend'),
            'existing_report_id' => $existing?->id,
        ];
    }

    private function approvedGroup(EmployeeDailySubmission $submission, bool $lock = false)
    {
        $query = EmployeeDailySubmission::whereDate('submission_date', $submission->submission_date)
            ->where('client_id', $submission->client_id)
            ->where('page_id', $submission->page_id)
            ->where('campaign_id', $submission->campaign_id)
            ->where('status', 'approved');

        return ($lock ? $query->lockForUpdate() : $query)->get();
    }
}
