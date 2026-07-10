<?php

namespace App\Services;

use App\Models\AdManagerReport;
use App\Models\AuditorReport;
use App\Models\Campaign;
use App\Models\ClientPage;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\ModeratorReport;
use App\Models\MonitorReport;
use App\Models\PageDailyOperationSummary;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EnterpriseMarketingOperationsService
{
    public const MODULES = [
        'moderator' => 'Moderator Operations',
        'ad-manager' => 'Ad Manager Operations',
        'auditor' => 'Auditor Operations',
        'monitor' => 'Monitor Operations',
    ];

    public const STATUS_FLOW = ['draft', 'submitted', 'late_submitted', 'verified', 'rejected', 'approved'];

    public function __construct(private readonly MarketingOperationsSettingsService $settings)
    {
    }

    public function dashboard(): array
    {
        $today = today()->toDateString();
        $moderator = ModeratorReport::whereDate('submission_date', $today)->get();
        $ad = AdManagerReport::whereDate('report_date', $today)->get();

        $missingModerator = now($this->settings->timezone())->gte($this->settings->missingReportCutoff('moderator'))
            ? $this->missingReportCount(ModeratorReport::class, 'submission_date')
            : 0;
        $missingAd = now($this->settings->timezone())->gte($this->settings->missingReportCutoff('ad-manager'))
            ? $this->missingReportCount(AdManagerReport::class, 'report_date')
            : 0;

        return [
            'today_orders' => (int) $moderator->sum('orders'),
            'today_spend' => (float) $ad->sum('spend_usd'),
            'today_revenue' => round((float) $ad->sum('spend_usd') * 145, 2),
            'today_estimated_profit' => round((float) $ad->sum('spend_usd') * 15, 2),
            'pending_reports' => $this->pendingReportsCount(),
            'pending_verifications' => $this->pendingVerificationCount(),
            'missing_moderator_reports' => $missingModerator,
            'missing_ad_reports' => $missingAd,
            'missing_auditor_reports' => $this->missingReportCount(AuditorReport::class, 'audit_date'),
            'missing_monitor_reports' => $this->missingReportCount(MonitorReport::class, 'review_date'),
            'on_time_reports' => ModeratorReport::whereDate('submission_date', $today)->where('status', 'submitted')->count()
                + AdManagerReport::whereDate('report_date', $today)->where('status', 'submitted')->count(),
            'late_reports' => ModeratorReport::whereDate('submission_date', $today)->where('status', 'late_submitted')->count()
                + AdManagerReport::whereDate('report_date', $today)->where('status', 'late_submitted')->count(),
        ];
    }

    public function query(string $module, array $filters = [], ?User $user = null): Builder
    {
        $query = $this->modelClass($module)::query()
            ->with($this->relationsFor($module))
            ->latest($this->dateColumn($module))
            ->latest();

        if ($user && ! $this->canManage($user) && $user->employee) {
            $query->where(function ($inner) use ($user, $module) {
                if ($module === 'monitor') {
                    $inner->where('reporter_employee_id', $user->employee->id)
                        ->orWhere('employee_id', $user->employee->id);
                } else {
                    $inner->where('employee_id', $user->employee->id);
                }
            });
        }

        foreach (['client_id', 'page_id', 'campaign_id', 'employee_id', 'status'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate($this->dateColumn($module), '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate($this->dateColumn($module), '<=', $filters['date_to']);
        }

        return $query;
    }

    public function store(Request $request, string $module, User $user): Model
    {
        abort_unless($this->canSubmit($user, $module), 403);

        $data = $this->validate($request, $module);
        $data['created_by'] = $user->id;
        $data['updated_by'] = $user->id;
        $data['employee_id'] = $data['employee_id'] ?? $user->employee?->id;
        if ($module === 'monitor') {
            $data['reporter_employee_id'] = $user->employee?->id;
        }
        $data['status'] = $data['status'] ?? $this->settings->statusForSubmission($module);
        $data = $this->resolveCampaignAndPage($data);
        $this->ensureAssignedScope($module, $data, $user);

        if (! $this->canManage($user) && $user->employee && $data['employee_id'] !== $user->employee->id) {
            throw ValidationException::withMessages(['employee_id' => 'You can only submit your own marketing operations reports.']);
        }

        $this->ensureDuplicateFree($module, $data);

        foreach (['attachment', 'screenshot'] as $file) {
            if ($request->hasFile($file)) {
                $column = $file === 'screenshot' ? 'screenshot_path' : 'attachment_path';
                $data[$column] = $request->file($file)->store('marketing-operations/enterprise', 'public');
            }
        }

        return DB::transaction(function () use ($module, $data) {
            $report = $this->modelClass($module)::create($data);
            $this->syncPageDailySummary($module, $report);

            return $report;
        });
    }

    public function updateStatus(string $module, int $id, string $status, User $user): Model
    {
        abort_unless($this->canManage($user), 403);
        abort_unless(in_array($status, self::STATUS_FLOW, true), 422);

        return DB::transaction(function () use ($module, $id, $status, $user) {
            $report = $this->modelClass($module)::whereKey($id)->lockForUpdate()->firstOrFail();

            if ($report->status === 'approved') {
                throw ValidationException::withMessages(['status' => 'Approved reports are locked.']);
            }

            $updates = ['status' => $status, 'updated_by' => $user->id];
            if ($status === 'verified') {
                $updates += ['verified_by' => $user->id, 'verified_at' => now()];
            }
            if ($status === 'approved') {
                $updates += ['verified_by' => $report->verified_by ?: $user->id, 'verified_at' => $report->verified_at ?: now(), 'approved_by' => $user->id, 'approved_at' => now()];
            }
            $report->update($updates);
            $this->syncPageDailySummary($module, $report->fresh());

            return $report->fresh();
        });
    }

    public function pageDailySummaries(array $filters = [])
    {
        return PageDailyOperationSummary::with(['client', 'page', 'campaign'])
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('summary_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('summary_date', '<=', $date))
            ->latest('summary_date')
            ->latest()
            ->paginate(30)
            ->withQueryString();
    }

    public function canManage(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasAnyPermission(['marketing_operations.manage', 'marketing_operations.verify', 'marketing_operations.approve', 'marketing_operations.agency']);
    }

    public function canSubmit(User $user, string $module): bool
    {
        if ($this->canManage($user)) {
            return true;
        }

        return $user->hasPermission('marketing_operations.submit') || (bool) $user->employee;
    }

    public function modelClass(string $module): string
    {
        return match ($module) {
            'moderator' => ModeratorReport::class,
            'ad-manager' => AdManagerReport::class,
            'auditor' => AuditorReport::class,
            'monitor' => MonitorReport::class,
            default => abort(404),
        };
    }

    public function dateColumn(string $module): string
    {
        return match ($module) {
            'moderator' => 'submission_date',
            'ad-manager' => 'report_date',
            'auditor' => 'audit_date',
            'monitor' => 'review_date',
            default => 'created_at',
        };
    }

    private function validate(Request $request, string $module): array
    {
        $base = [
            'client_id' => ['nullable', 'exists:clients,id'],
            'page_id' => ['nullable', 'exists:client_pages,id'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'status' => ['nullable', Rule::in(self::STATUS_FLOW)],
            'notes' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xlsx,zip', 'max:10240'],
            'screenshot' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];

        return $request->validate(match ($module) {
            'moderator' => array_merge($base, [
                'client_id' => ['required', 'exists:clients,id'],
                'page_id' => ['required', 'exists:client_pages,id'],
                'submission_date' => ['required', 'date'],
                'orders' => ['required', 'integer', 'min:0'],
                'confirmed_orders' => ['required', 'integer', 'min:0'],
                'cancelled_orders' => ['required', 'integer', 'min:0'],
                'pending_orders' => ['required', 'integer', 'min:0'],
                'returned_orders' => ['nullable', 'integer', 'min:0'],
            ]),
            'ad-manager' => array_merge($base, [
                'client_id' => ['required', 'exists:clients,id'],
                'campaign_id' => ['required', 'exists:campaigns,id'],
                'report_date' => ['required', 'date'],
                'spend_usd' => ['required', 'numeric', 'min:0'],
                'spend_bdt' => ['nullable', 'numeric', 'min:0'],
                'purchases' => ['required', 'integer', 'min:0'],
                'cpm' => ['nullable', 'numeric', 'min:0'],
                'ctr' => ['nullable', 'numeric', 'min:0'],
                'cpc' => ['nullable', 'numeric', 'min:0'],
                'frequency' => ['nullable', 'numeric', 'min:0'],
                'reach' => ['nullable', 'integer', 'min:0'],
                'impressions' => ['nullable', 'integer', 'min:0'],
            ]),
            'auditor' => array_merge($base, [
                'client_id' => ['required', 'exists:clients,id'],
                'page_id' => ['required', 'exists:client_pages,id'],
                'moderator_id' => ['nullable', 'exists:employees,id'],
                'audit_date' => ['required', 'date'],
                'average_response_time' => ['required', 'numeric', 'min:0'],
                'longest_delay' => ['required', 'numeric', 'min:0'],
                'total_delayed_replies' => ['required', 'integer', 'min:0'],
                'qa_score' => ['required', 'numeric', 'min:0', 'max:100'],
                'message_quality' => ['required', 'numeric', 'min:0', 'max:100'],
                'greeting_score' => ['required', 'numeric', 'min:0', 'max:100'],
                'closing_score' => ['required', 'numeric', 'min:0', 'max:100'],
                'follow_up_score' => ['required', 'numeric', 'min:0', 'max:100'],
                'remarks' => ['nullable', 'string'],
                'overall_status' => ['required', Rule::in(['excellent', 'good', 'average', 'poor', 'critical'])],
            ]),
            'monitor' => array_merge($base, [
                'employee_id' => ['required', 'exists:employees,id'],
                'department_id' => ['nullable', 'exists:departments,id'],
                'review_date' => ['required', 'date'],
                'issue_type' => ['required', 'string', 'max:120'],
                'description' => ['required', 'string'],
                'severity' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
                'recommendation' => ['nullable', 'string'],
                'resolution_status' => ['required', Rule::in(['pending', 'resolved', 'escalated'])],
            ]),
            default => [],
        });
    }

    private function resolveCampaignAndPage(array $data): array
    {
        if (! empty($data['campaign_id'])) {
            $campaign = Campaign::findOrFail($data['campaign_id']);
            $data['client_id'] = $campaign->client_id ?: $data['client_id'] ?? null;
            $data['page_id'] = $campaign->client_page_id ?: $data['page_id'] ?? null;
        }

        if (! empty($data['page_id']) && empty($data['client_id'])) {
            $data['client_id'] = ClientPage::whereKey($data['page_id'])->value('client_id');
        }

        if (($data['client_id'] ?? null) && ($data['page_id'] ?? null)) {
            $pageClientId = ClientPage::whereKey($data['page_id'])->value('client_id');
            if ($pageClientId && (int) $pageClientId !== (int) $data['client_id']) {
                throw ValidationException::withMessages(['page_id' => 'Selected page does not belong to selected client.']);
            }
        }

        if (array_key_exists('spend_usd', $data)) {
            $data['cpp'] = AdManagerReport::costPer((float) $data['spend_usd'], (int) ($data['purchases'] ?? 0));
            $data['spend_bdt'] = $data['spend_bdt'] ?? round((float) $data['spend_usd'] * 145, 2);
        }

        return $data;
    }

    private function ensureDuplicateFree(string $module, array $data): void
    {
        $query = $this->modelClass($module)::query();
        match ($module) {
            'moderator' => $query->where('page_id', $data['page_id'])->whereDate('submission_date', $data['submission_date']),
            'ad-manager' => $query->where('campaign_id', $data['campaign_id'])->whereDate('report_date', $data['report_date']),
            'auditor' => $query->where('page_id', $data['page_id'])->where('moderator_id', $data['moderator_id'] ?? null)->whereDate('audit_date', $data['audit_date']),
            'monitor' => $query->where('employee_id', $data['employee_id'])->where('issue_type', $data['issue_type'])->whereDate('review_date', $data['review_date']),
        };

        if ($query->exists()) {
            throw ValidationException::withMessages(['date' => 'A report already exists for this daily operational scope.']);
        }
    }

    private function syncPageDailySummary(string $module, Model $report): void
    {
        $date = $report->{$this->dateColumn($module)};
        $pageId = $report->page_id ?? null;
        $campaignId = $report->campaign_id ?? null;
        if (! $date || (! $pageId && ! $campaignId)) {
            return;
        }

        $summary = PageDailyOperationSummary::whereDate('summary_date', $date->toDateString())
            ->where('page_id', $pageId)
            ->where('campaign_id', $campaignId)
            ->first() ?: new PageDailyOperationSummary([
                'summary_date' => $date->toDateString(),
                'page_id' => $pageId,
                'campaign_id' => $campaignId,
            ]);
        $summary->client_id = $report->client_id ?? $summary->client_id;
        $summary->{$module === 'moderator' ? 'moderator_report_id' : ($module === 'ad-manager' ? 'ad_manager_report_id' : ($module === 'auditor' ? 'auditor_report_id' : 'monitor_report_id'))} = $report->id;

        if ($module === 'moderator') {
            $summary->orders = (int) $report->orders;
        }
        if ($module === 'ad-manager') {
            $summary->spend_usd = (float) $report->spend_usd;
            $summary->cpp = (float) $report->cpp;
            $summary->revenue = round((float) $report->spend_usd * 145, 2);
            $summary->profit = round((float) $report->spend_usd * 15, 2);
        }

        $summary->final_status = $report->status === 'approved' ? 'approved' : 'pending';
        $summary->approved_by = $report->approved_by ?: $summary->approved_by;
        $summary->approved_at = $report->approved_at ?: $summary->approved_at;
        $summary->save();
    }

    private function ensureAssignedScope(string $module, array $data, User $user): void
    {
        if ($this->canManage($user) || ! $user->employee || ! in_array($module, ['moderator', 'ad-manager'], true)) {
            return;
        }

        $query = EmployeeAssignment::query()
            ->where('employee_id', $user->employee->id)
            ->where('status', 'active');

        if (! empty($data['campaign_id'])) {
            $query->where(function ($inner) use ($data) {
                $inner->where('campaign_id', $data['campaign_id'])
                    ->orWhere('client_page_id', $data['page_id'] ?? 0);
            });
        } elseif (! empty($data['page_id'])) {
            $query->where('client_page_id', $data['page_id']);
        }

        if (! $query->exists()) {
            throw ValidationException::withMessages(['page_id' => 'You can only submit reports for assigned pages or campaigns.']);
        }
    }

    private function relationsFor(string $module): array
    {
        return match ($module) {
            'moderator', 'ad-manager' => ['client', 'page', 'campaign', 'employee'],
            'auditor' => ['client', 'page', 'moderator', 'employee'],
            'monitor' => ['employee', 'department', 'client', 'page', 'reporter'],
            default => [],
        };
    }

    private function pendingReportsCount(): int
    {
        return ModeratorReport::whereIn('status', ['draft', 'submitted', 'late_submitted'])->count()
            + AdManagerReport::whereIn('status', ['draft', 'submitted', 'late_submitted'])->count()
            + AuditorReport::whereIn('status', ['draft', 'submitted', 'late_submitted'])->count()
            + MonitorReport::whereIn('status', ['draft', 'submitted', 'late_submitted'])->count();
    }

    private function pendingVerificationCount(): int
    {
        return ModeratorReport::where('status', 'submitted')->count()
            + AdManagerReport::where('status', 'submitted')->count()
            + AuditorReport::where('status', 'submitted')->count()
            + MonitorReport::where('status', 'submitted')->count();
    }

    private function missingReportCount(string $model, string $dateColumn): int
    {
        return max(0, 1 - $model::whereDate($dateColumn, today()->toDateString())->count());
    }
}
