<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientPage;
use App\Models\Employee;
use App\Models\EmployeeWorkStatus;
use App\Models\Shift;
use App\Services\ActivityLogger;
use App\Services\AssignmentResolver;
use App\Services\WorkStatusCycleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class EmployeeWorkStatusController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $rows = $this->filteredQuery($filters)->latest('work_date')->paginate(25)->withQueryString();
        $summaryRows = $this->filteredQuery($filters)->get();

        return view('admin.work-status.index', [
            'workStatuses' => $rows,
            'employees' => $this->availableEmployees(),
            'clients' => Client::orderBy('company_name')->get(),
            'clientPages' => ClientPage::orderBy('page_name')->get(),
            'campaigns' => Campaign::orderBy('campaign_name')->get(),
            'shifts' => Shift::where('status', 'active')->orderBy('id')->get(),
            'statuses' => EmployeeWorkStatus::STATUSES,
            'filters' => $filters,
            'summary' => [
                'working_days' => (float) $summaryRows->sum('salary_count_value'),
                'half_days' => $summaryRows->where('status', 'half_day')->count(),
                'leave' => $summaryRows->where('status', 'on_leave')->count(),
                'client_issue' => $summaryRows->where('status', 'client_issue')->count(),
                'boosting_off' => $summaryRows->where('status', 'boosting_off')->count(),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $prefill = $request->validate([
            'entry_mode' => ['nullable', Rule::in(['single', 'range', 'monthly'])],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'salary_month' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', Rule::in(array_keys(EmployeeWorkStatus::STATUSES))],
            'note' => ['nullable', 'string', 'max:500'],
            'return_to' => ['nullable', 'string', 'max:2048'],
        ]);
        $prefill['return_to'] = $this->safeReturnTo($prefill['return_to'] ?? null);

        $employees = Employee::with(['activeAssignments.page', 'activeAssignments.campaignRecord', 'activeAssignments.shift'])
            ->when($this->moderatorEmployeeId(), fn ($query, $employeeId) => $query->whereKey($employeeId))
            ->orderBy('name')
            ->get();
        $assignmentDefaults = $employees
            ->mapWithKeys(function (Employee $employee) {
                $assignment = app(AssignmentResolver::class)->current($employee);
                $finalSettlementPeriod = null;

                if ($employee->status === 'terminated' && $employee->last_working_date) {
                    try {
                        $period = app(WorkStatusCycleService::class)->period($employee, $employee->last_working_date->format('Y-m'));
                        $finalSettlementPeriod = [
                            'salary_cycle_date' => $period['salary_cycle_date']->toDateString(),
                            'period_start' => $period['period_start']->toDateString(),
                            'period_end' => $period['period_end']->toDateString(),
                        ];
                    } catch (ValidationException $exception) {
                        $finalSettlementPeriod = null;
                    }
                }

                return [$employee->id => [
                    'employee_id' => $employee->id,
                    'has_assignment' => (bool) $assignment,
                    'is_agency_internal' => $employee->isAgencyInternal(),
                    'status' => $employee->status,
                    'client_id' => $assignment?->client_id,
                    'client_page_id' => $assignment?->client_page_id,
                    'campaign_id' => $assignment?->campaign_id,
                    'shift_id' => $assignment?->shift_id ?: $employee->shift_id,
                    'confirmation_date' => $employee->confirmation_date?->toDateString(),
                    'last_working_date' => $employee->last_working_date?->toDateString(),
                    'salary_day' => $employee->salaryCycleDay(),
                    'final_settlement_period' => $finalSettlementPeriod,
                ]];
            })
            ->all();

        return view('admin.work-status.create', [
            'employees' => $employees,
            'clients' => Client::orderBy('company_name')->get(),
            'clientPages' => ClientPage::orderBy('page_name')->get(),
            'campaigns' => Campaign::orderBy('campaign_name')->get(),
            'shifts' => Shift::where('status', 'active')->orderBy('id')->get(),
            'statuses' => EmployeeWorkStatus::STATUSES,
            'salaryCountValues' => EmployeeWorkStatus::SALARY_COUNT_VALUES,
            'assignmentDefaults' => $assignmentDefaults,
            'prefill' => $prefill,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        if (($data['entry_mode'] ?? 'single') === 'monthly') {
            return $this->storeMonthlyCycle($data, $request);
        }

        if (($data['entry_mode'] ?? 'single') === 'range') {
            return $this->storeDateRange($data, $request);
        }

        $employee = Employee::findOrFail($data['employee_id']);
        $this->ensureWorkDateAllowed($employee, Carbon::parse($data['work_date']), (bool) ($data['confirm_after_last_working_date'] ?? false));
        $workStatus = $this->saveWorkStatusForDate($data, $data['work_date']);

        app(ActivityLogger::class)->log('Work Status', 'Work Status Created', 'Work status #' . $workStatus->id . ' saved for ' . $workStatus->work_date?->toDateString() . '.', $request);

        return redirect($this->safeReturnTo($data['return_to'] ?? null) ?: '/admin/work-status/' . $workStatus->id . '/edit')
            ->with('success', 'Work status saved successfully.');
    }

    private function storeMonthlyCycle(array $data, Request $request)
    {
        $employee = Employee::findOrFail($data['employee_id']);
        $expectedDates = app(WorkStatusCycleService::class)->dates($employee, $data['salary_month']);
        $submittedRows = collect($data['monthly_rows'] ?? [])->keyBy('date');
        $duplicateAction = $data['duplicate_action'] ?? 'skip';

        if ($employee->isAgencyInternal()) {
            $data['client_id'] = null;
            $data['client_page_id'] = null;
            $data['campaign_id'] = null;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($expectedDates, $submittedRows, $duplicateAction, $data, &$created, &$updated, &$skipped) {
            foreach ($expectedDates as $date) {
                $existing = EmployeeWorkStatus::where('employee_id', $data['employee_id'])
                    ->whereDate('work_date', $date)
                    ->first();

                if ($existing && $duplicateAction === 'skip') {
                    $skipped++;
                    continue;
                }

                $row = $submittedRows->get($date, []);
                $rowData = $data;
                $rowData['status'] = $row['status'] ?? $data['status'];
                $rowData['note'] = $row['note'] ?? $data['note'] ?? null;
                $workStatus = $this->saveWorkStatusForDate($rowData, $date);

                $workStatus->wasRecentlyCreated ? $created++ : $updated++;
            }
        });

        app(ActivityLogger::class)->log(
            'Work Status',
            'Bulk Work Status Created',
            "Monthly cycle work status saved. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}.",
            $request
        );

        return redirect($this->safeReturnTo($data['return_to'] ?? null) ?: '/admin/payroll?status=due')->with(
            'success',
            "Monthly cycle work status saved. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}."
        );
    }

    private function storeDateRange(array $data, Request $request)
    {
        $employee = Employee::findOrFail($data['employee_id']);
        $fromDate = Carbon::parse($data['from_date'])->startOfDay();
        $toDate = Carbon::parse($data['to_date'])->startOfDay();

        if ($employee->salaryEligibilityDate() && $fromDate->lt($employee->salaryEligibilityDate())) {
            throw ValidationException::withMessages([
                'from_date' => 'Work Status cannot be added before the employee confirmation date.',
            ]);
        }

        if ($employee->last_working_date && ! ($data['confirm_after_last_working_date'] ?? false)) {
            $lastWorkingDate = $employee->last_working_date->copy()->startOfDay();

            if ($toDate->gt($lastWorkingDate)) {
                throw ValidationException::withMessages([
                    'to_date' => 'This range includes dates after the employee last working date. Confirm override to continue.',
                ]);
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($employee, $data, $fromDate, $toDate, &$created, &$updated, &$skipped) {
            for ($date = $fromDate->copy(); $date->lte($toDate); $date->addDay()) {
                if ($employee->last_working_date
                    && $date->gt($employee->last_working_date->copy()->startOfDay())
                    && ! ($data['confirm_after_last_working_date'] ?? false)) {
                    $skipped++;
                    continue;
                }

                $workStatus = $this->saveWorkStatusForDate($data, $date->toDateString());
                $workStatus->wasRecentlyCreated ? $created++ : $updated++;
            }
        });

        app(ActivityLogger::class)->log(
            'Work Status',
            'Bulk Work Status Created',
            "Bulk work status saved. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}.",
            $request
        );

        return redirect($this->safeReturnTo($data['return_to'] ?? null) ?: '/admin/work-status')->with(
            'success',
            "Bulk work status saved. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}."
        );
    }

    private function saveWorkStatusForDate(array $data, string $date): EmployeeWorkStatus
    {
        $workStatus = EmployeeWorkStatus::where('employee_id', $data['employee_id'])
            ->whereDate('work_date', $date)
            ->first();

        if (! $workStatus) {
            $workStatus = new EmployeeWorkStatus([
                'employee_id' => $data['employee_id'],
                'work_date' => $date,
                'created_by' => auth()->id(),
            ]);
        }

        $workStatus->fill([
            'client_id' => $data['client_id'] ?? null,
            'client_page_id' => $data['client_page_id'] ?? null,
            'campaign_id' => $data['campaign_id'] ?? null,
            'shift_id' => $data['shift_id'] ?? null,
            'status' => $data['status'],
            'salary_count_value' => EmployeeWorkStatus::salaryCountFor($data['status']),
            'note' => $data['note'] ?? null,
            'updated_by' => auth()->id(),
        ])->save();

        return $workStatus;
    }

    public function edit(EmployeeWorkStatus $workStatus)
    {
        $this->authorizeModeratorRecord($workStatus);

        return view('admin.work-status.edit', [
            'workStatus' => $workStatus->load(['employee', 'client', 'page', 'campaign', 'shift']),
            'employees' => $this->availableEmployees(),
            'clients' => Client::orderBy('company_name')->get(),
            'clientPages' => ClientPage::orderBy('page_name')->get(),
            'campaigns' => Campaign::orderBy('campaign_name')->get(),
            'shifts' => Shift::where('status', 'active')->orderBy('id')->get(),
            'statuses' => EmployeeWorkStatus::STATUSES,
        ]);
    }

    public function update(Request $request, EmployeeWorkStatus $workStatus)
    {
        $this->authorizeModeratorRecord($workStatus);
        $data = $request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'client_page_id' => ['nullable', 'exists:client_pages,id'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'status' => ['required', Rule::in(array_keys(EmployeeWorkStatus::STATUSES))],
            'salary_count_value' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $this->ensureOperationalHierarchy(array_merge($data, ['client_id' => $data['client_id'] ?? null]));

        $workStatus->fill([
            'client_id' => $data['client_id'] ?? null,
            'client_page_id' => $data['client_page_id'] ?? null,
            'campaign_id' => $data['campaign_id'] ?? null,
            'shift_id' => $data['shift_id'] ?? null,
            'status' => $data['status'],
            'salary_count_value' => array_key_exists('salary_count_value', $data)
                ? (float) $data['salary_count_value']
                : EmployeeWorkStatus::salaryCountFor($data['status']),
            'note' => $data['note'] ?? null,
            'updated_by' => auth()->id(),
        ])->save();

        app(ActivityLogger::class)->log('Work Status', 'Work Status Updated', 'Work status #' . $workStatus->id . ' updated.', $request);

        return redirect('/admin/work-status')->with('success', 'Work status updated successfully.');
    }

    public function destroy(EmployeeWorkStatus $workStatus)
    {
        $this->authorizeModeratorRecord($workStatus);
        $description = 'Work status #' . $workStatus->id . ' for ' . $workStatus->work_date?->toDateString() . ' deleted.';
        $workStatus->delete();

        app(ActivityLogger::class)->log('Work Status', 'Work Status Deleted', $description, request());

        return redirect('/admin/work-status')->with('success', 'Work status deleted successfully.');
    }

    public function export(Request $request)
    {
        $rows = $this->filteredQuery($this->filters($request))
            ->latest('work_date')
            ->get();

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Employee', 'Client', 'Page', 'Campaign', 'Shift', 'Status', 'Salary Count Value', 'Note']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->work_date?->toDateString(),
                    trim(($row->employee?->employee_id ?: '-') . ' ' . ($row->employee?->name ?: '')),
                    $row->client?->company_name ?: '-',
                    $row->page?->page_name ?: '-',
                    $row->campaign?->campaign_name ?: '-',
                    $row->shift?->name ?: '-',
                    $row->statusLabel(),
                    number_format((float) $row->salary_count_value, 2, '.', ''),
                    $row->note ?: '-',
                ]);
            }

            fclose($handle);
        }, 'employee-work-status-report.csv', ['Content-Type' => 'text/csv']);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['nullable', 'exists:employees,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', Rule::in(array_keys(EmployeeWorkStatus::STATUSES))],
        ]);
    }

    private function filteredQuery(array $filters)
    {
        return EmployeeWorkStatus::with(['employee', 'client', 'page', 'campaign', 'shift'])
            ->when($this->moderatorEmployeeId(), fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when($filters['client_id'] ?? null, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->when($filters['campaign_id'] ?? null, fn ($query, $campaignId) => $query->where('campaign_id', $campaignId))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('work_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('work_date', '<=', $date))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'entry_mode' => ['nullable', Rule::in(['single', 'range', 'monthly'])],
            'employee_id' => ['required', 'exists:employees,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'client_page_id' => ['nullable', 'exists:client_pages,id'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'work_date' => ['required_if:entry_mode,single', 'nullable', 'date'],
            'from_date' => ['required_if:entry_mode,range', 'nullable', 'date'],
            'to_date' => ['required_if:entry_mode,range', 'nullable', 'date', 'after_or_equal:from_date'],
            'salary_month' => ['required_if:entry_mode,monthly', 'nullable', 'date_format:Y-m'],
            'duplicate_action' => ['nullable', Rule::in(['skip', 'update'])],
            'monthly_rows' => ['required_if:entry_mode,monthly', 'nullable', 'array', 'max:32'],
            'monthly_rows.*.date' => ['required', 'date'],
            'monthly_rows.*.day_type' => ['nullable', Rule::in(['working', 'half_day', 'non_working'])],
            'monthly_rows.*.status' => ['required', Rule::in(array_keys(EmployeeWorkStatus::STATUSES))],
            'monthly_rows.*.note' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(array_keys(EmployeeWorkStatus::STATUSES))],
            'note' => ['nullable', 'string', 'max:500'],
            'confirm_after_last_working_date' => ['nullable', 'boolean'],
            'return_to' => ['nullable', 'string', 'max:2048'],
        ]);

        $data['entry_mode'] = $data['entry_mode'] ?? 'single';

        if ($employeeId = $this->moderatorEmployeeId()) {
            abort_unless((int) $data['employee_id'] === $employeeId, 403);
            $assignments = auth()->user()->employee?->activeAssignments;
            if (! empty($data['client_id'])) {
                abort_unless($assignments?->contains('client_id', (int) $data['client_id']), 403);
            }
            if (! empty($data['client_page_id'])) {
                abort_unless($assignments?->contains('client_page_id', (int) $data['client_page_id']), 403);
            }
            if (! empty($data['campaign_id'])) {
                abort_unless($assignments?->contains('campaign_id', (int) $data['campaign_id']), 403);
            }
        }

        if ($data['entry_mode'] === 'range' && ! empty($data['from_date']) && ! empty($data['to_date'])) {
            $days = Carbon::parse($data['from_date'])->diffInDays(Carbon::parse($data['to_date'])) + 1;

            if ($days > 60) {
                throw ValidationException::withMessages([
                    'to_date' => 'Date range should not exceed 60 days at once.',
                ]);
            }
        }

        $this->ensureOperationalHierarchy($data);

        return $data;
    }

    private function ensureWorkDateAllowed(Employee $employee, Carbon $date, bool $allowAfterLastWorkingDate): void
    {
        $date = $date->copy()->startOfDay();

        if ($employee->salaryEligibilityDate() && $date->lt($employee->salaryEligibilityDate())) {
            throw ValidationException::withMessages(['work_date' => 'Work Status cannot be added before the employee confirmation date.']);
        }

        if ($employee->last_working_date && $date->gt($employee->last_working_date) && ! $allowAfterLastWorkingDate) {
            throw ValidationException::withMessages(['work_date' => 'Work Status cannot be added after the employee last working date without override confirmation.']);
        }
    }

    private function ensureOperationalHierarchy(array $data): void
    {
        $clientId = isset($data['client_id']) ? (int) $data['client_id'] : null;
        $page = ! empty($data['client_page_id']) ? ClientPage::find($data['client_page_id']) : null;
        $campaign = ! empty($data['campaign_id']) ? Campaign::find($data['campaign_id']) : null;

        if ($page && $clientId && (int) $page->client_id !== $clientId) {
            throw ValidationException::withMessages(['client_page_id' => 'Selected page does not belong to the selected client.']);
        }

        if ($campaign && $clientId && (int) $campaign->client_id !== $clientId) {
            throw ValidationException::withMessages(['campaign_id' => 'Selected campaign does not belong to the selected client.']);
        }

        if ($campaign && $page && (int) $campaign->client_page_id !== (int) $page->id) {
            throw ValidationException::withMessages(['campaign_id' => 'Selected campaign does not belong to the selected page.']);
        }
    }

    private function safeReturnTo(?string $returnTo): ?string
    {
        if (! $returnTo || ! str_starts_with($returnTo, '/admin/payroll')) {
            return null;
        }

        return $returnTo;
    }

    private function moderatorEmployeeId(): ?int
    {
        if (! auth()->user()?->hasRole('moderator')) {
            return null;
        }

        $employeeId = auth()->user()->employee?->id;
        abort_unless($employeeId, 403, 'Moderator account is not linked to an employee.');

        return (int) $employeeId;
    }

    private function availableEmployees()
    {
        return Employee::query()
            ->when($this->moderatorEmployeeId(), fn ($query, $employeeId) => $query->whereKey($employeeId))
            ->orderBy('name')
            ->get();
    }

    private function authorizeModeratorRecord(EmployeeWorkStatus $workStatus): void
    {
        if ($employeeId = $this->moderatorEmployeeId()) {
            abort_unless((int) $workStatus->employee_id === $employeeId, 403);
        }
    }
}
