<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\ClientPage;
use App\Models\Employee;
use App\Models\MarketingOperationsReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarketingOperationsService
{
    public function __construct(private AssignmentResolver $assignments) {}

    public function query(array $filters = [], ?User $user = null): Builder
    {
        $query = MarketingOperationsReport::with([
            'employee', 'targetEmployee', 'client', 'page', 'campaign', 'adAccount', 'reviewer',
        ])->latest('report_date')->latest();

        if ($user && ! $this->canManage($user) && $user->employee) {
            $query->where(function ($inner) use ($user) {
                $inner->where('employee_id', $user->employee->id)
                    ->orWhere('target_employee_id', $user->employee->id);
            });
        }

        foreach (['report_type', 'platform', 'status', 'client_id', 'page_id', 'employee_id'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('report_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('report_date', '<=', $filters['date_to']);
        }

        return $query;
    }

    public function store(Request $request, string $type, User $user): MarketingOperationsReport
    {
        $employee = $user->employee;
        $data = $this->validateReport($request, $type);
        $data['report_type'] = $type;
        $data['platform'] = $data['platform'] ?? 'Meta';
        $data['employee_id'] = $employee?->id;
        $data['created_by'] = $user->id;
        $data['updated_by'] = $user->id;
        $data['status'] = $type === 'monitor_issue' ? 'open' : 'pending';
        $data = $this->resolveRelations($data, $user);
        $data['duplicate_key'] = MarketingOperationsReport::duplicateKey($data);

        if (MarketingOperationsReport::where('duplicate_key', $data['duplicate_key'])->exists()) {
            throw ValidationException::withMessages(['report_date' => 'A marketing operations report already exists for this daily scope.']);
        }

        if ($request->hasFile('screenshot')) {
            $data['screenshot_path'] = $request->file('screenshot')->store('marketing-operations/screenshots', 'public');
        }
        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('marketing-operations/attachments', 'public');
        }

        return MarketingOperationsReport::create($data);
    }

    public function updateStatus(MarketingOperationsReport $report, string $status, ?string $adminNote, User $user): void
    {
        DB::transaction(function () use ($report, $status, $adminNote, $user) {
            $locked = MarketingOperationsReport::whereKey($report->id)->lockForUpdate()->firstOrFail();
            $locked->update([
                'status' => $status,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'admin_note' => $adminNote,
                'updated_by' => $user->id,
            ]);
        });
    }

    public function verificationGroups(array $filters = [])
    {
        return $this->query($filters, auth()->user())
            ->get()
            ->groupBy(fn (MarketingOperationsReport $report) => implode(':', [
                $report->report_date?->toDateString(),
                $report->platform,
                $report->client_id ?: 0,
                $report->page_id ?: 0,
                $report->campaign_id ?: 0,
            ]));
    }

    public function widgets(): array
    {
        $reports = MarketingOperationsReport::with(['employee', 'targetEmployee', 'page'])
            ->whereDate('report_date', '>=', now()->subDays(30)->toDateString())
            ->get();

        return [
            'top_moderator' => $this->topEmployee($reports->where('report_type', 'moderator_order'), 'confirmed_orders'),
            'top_ad_manager' => $this->topEmployee($reports->where('report_type', 'ad_manager_spend'), 'dollar_spend'),
            'top_auditor' => $this->topEmployee($reports->where('report_type', 'auditor_audit'), null),
            'top_monitor' => $this->topEmployee($reports->where('report_type', 'monitor_issue'), null),
            'top_trainer' => $this->topEmployee($reports->where('report_type', 'trainer_training'), null),
            'most_reported_issues' => $reports->where('report_type', 'monitor_issue')->count(),
            'repeated_mistakes' => $reports->where('status', 'repeated')->count(),
            'training_due' => $reports->where('report_type', 'trainer_training')->filter(fn ($report) => ! empty($report->metrics['next_training_date']) && $report->metrics['next_training_date'] <= today()->toDateString())->count(),
            'critical_operations_status' => $reports->where('report_type', 'management_review')->where('severity', 'High')->count(),
            'average_response_time' => round((float) $reports->where('report_type', 'auditor_audit')->avg(fn ($report) => (float) ($report->metrics['average_response_time'] ?? 0)), 2),
            'average_cpp' => round((float) $reports->where('report_type', 'ad_manager_spend')->avg(fn ($report) => (float) ($report->metrics['cost_per_purchase'] ?? 0)), 2),
        ];
    }

    public function canManage(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasAnyPermission(['agency_operations.manage', 'agency_operations.verify', 'agency_operations.approve']);
    }

    public function canSubmit(User $user, string $type): bool
    {
        if ($this->canManage($user)) {
            return true;
        }

        return $user->hasAnyPermission([
            'moderator_operations.submit',
            'ad_manager_operations.submit',
            'auditor_operations.submit',
            'monitor_operations.submit',
        ]) || (bool) $user->employee;
    }

    private function validateReport(Request $request, string $type): array
    {
        $base = [
            'report_date' => ['required', 'date'],
            'platform' => ['nullable', 'in:' . implode(',', MarketingOperationsReport::PLATFORMS)],
            'client_id' => ['nullable', 'exists:clients,id'],
            'page_id' => ['nullable', 'exists:client_pages,id'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
            'ad_account_id' => ['nullable', 'exists:ad_accounts,id'],
            'target_employee_id' => ['nullable', 'exists:employees,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'role_id' => ['nullable', 'exists:employee_roles,id'],
            'severity' => ['nullable', 'in:Low,Medium,High'],
            'notes' => ['nullable', 'string'],
            'screenshot' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xlsx,zip', 'max:10240'],
        ];

        $metrics = match ($type) {
            'moderator_order' => [
                'confirmed_orders' => ['required', 'integer', 'min:0'],
                'cancelled_orders' => ['required', 'integer', 'min:0'],
                'pending_orders' => ['required', 'integer', 'min:0'],
                'returned_orders' => ['nullable', 'integer', 'min:0'],
                'replacement_orders' => ['nullable', 'integer', 'min:0'],
                'page_id' => ['required', 'exists:client_pages,id'],
            ],
            'ad_manager_spend' => [
                'dollar_spend' => ['required', 'numeric', 'min:0'],
                'currency' => ['nullable', 'string', 'max:10'],
                'cost_per_purchase' => ['required', 'numeric', 'min:0'],
                'impressions' => ['nullable', 'integer', 'min:0'],
                'clicks' => ['nullable', 'integer', 'min:0'],
                'ctr' => ['nullable', 'numeric', 'min:0'],
                'cpm' => ['nullable', 'numeric', 'min:0'],
                'cpc' => ['nullable', 'numeric', 'min:0'],
                'campaign_id' => ['required', 'exists:campaigns,id'],
            ],
            'auditor_audit' => [
                'average_response_time' => ['required', 'numeric', 'min:0'],
                'maximum_delay' => ['required', 'numeric', 'min:0'],
                'missed_messages' => ['required', 'integer', 'min:0'],
                'delayed_conversations' => ['required', 'integer', 'min:0'],
                'wrong_replies' => ['required', 'integer', 'min:0'],
                'follow_up_quality' => ['required', 'integer', 'min:0', 'max:100'],
                'response_quality' => ['required', 'integer', 'min:0', 'max:100'],
                'customer_handling' => ['required', 'integer', 'min:0', 'max:100'],
                'target_employee_id' => ['required', 'exists:employees,id'],
                'page_id' => ['required', 'exists:client_pages,id'],
                'severity' => ['required', 'in:Low,Medium,High'],
                'screenshot' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            ],
            'monitor_issue' => [
                'mistake_category' => ['required', 'string', 'max:100'],
                'correction_suggestion' => ['required', 'string'],
                'target_employee_id' => ['required', 'exists:employees,id'],
                'severity' => ['required', 'in:Low,Medium,High'],
            ],
            'trainer_training' => [
                'training_type' => ['required', 'string', 'max:100'],
                'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'pass_fail' => ['nullable', 'in:Pass,Fail'],
                'observation' => ['nullable', 'string'],
                'improvement_needed' => ['nullable', 'string'],
                'next_training_date' => ['nullable', 'date'],
                'target_employee_id' => ['required', 'exists:employees,id'],
            ],
            'management_review' => [
                'daily_summary' => ['required', 'string'],
                'today_issues' => ['nullable', 'string'],
                'resolved_issues' => ['nullable', 'string'],
                'pending_issues' => ['nullable', 'string'],
                'high_priority_issues' => ['nullable', 'string'],
                'escalations' => ['nullable', 'string'],
                'recommendations' => ['nullable', 'string'],
                'operations_status' => ['required', 'in:Good,Warning,Critical'],
            ],
            default => [],
        };

        $data = $request->validate(array_merge($base, $metrics));
        $metricKeys = array_keys($metrics);
        $relationKeys = ['page_id', 'campaign_id', 'ad_account_id', 'target_employee_id', 'department_id', 'role_id', 'severity'];
        $fileKeys = ['screenshot', 'attachment'];
        $metricOnlyKeys = array_values(array_diff($metricKeys, $relationKeys, $fileKeys));

        $data['metrics'] = collect($data)->only($metricOnlyKeys)->all();

        return collect($data)->except(array_merge($metricOnlyKeys, $fileKeys))->all();
    }

    private function resolveRelations(array $data, User $user): array
    {
        if (! empty($data['campaign_id'])) {
            $campaign = Campaign::with(['page', 'client'])->findOrFail($data['campaign_id']);
            $data['page_id'] = $campaign->client_page_id ?: $data['page_id'] ?? null;
            $data['client_id'] = $campaign->client_id ?: $data['client_id'] ?? null;
            $data['ad_account_id'] = $campaign->ad_account_id ?: $data['ad_account_id'] ?? null;
        }

        if (! empty($data['page_id'])) {
            $page = ClientPage::findOrFail($data['page_id']);
            if (! empty($data['client_id']) && (int) $data['client_id'] !== (int) $page->client_id) {
                throw ValidationException::withMessages(['page_id' => 'Selected page does not belong to selected client.']);
            }
            $data['client_id'] = $page->client_id;
        }

        if (! $this->canManage($user) && $user->employee && in_array($data['report_type'], ['moderator_order', 'ad_manager_spend'], true)) {
            $assignmentIds = $this->assignments->allCurrent($user->employee, \Carbon\Carbon::parse($data['report_date']))->pluck('client_page_id')->filter()->map(fn ($id) => (int) $id);
            if (! $assignmentIds->isEmpty() && ! $assignmentIds->contains((int) ($data['page_id'] ?? 0))) {
                throw ValidationException::withMessages(['page_id' => 'You can only submit reports for assigned pages.']);
            }
        }

        return $data;
    }

    private function topEmployee($reports, ?string $metric): ?string
    {
        if ($reports->isEmpty()) {
            return null;
        }

        $grouped = $reports->groupBy('employee_id')->map(function ($items) use ($metric) {
            return $metric
                ? $items->sum(fn ($report) => (float) ($report->metrics[$metric] ?? 0))
                : $items->count();
        });
        $employeeId = $grouped->sortDesc()->keys()->first();

        return Employee::find($employeeId)?->name;
    }
}
