<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeePayroll;
use App\Models\EmployeeWorkStatus;
use App\Services\ClientFundDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployeePayrollController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $data = $this->filteredPayrollData($filters);

        return view('admin.payroll.index', [
            'filters' => $filters,
            'payrolls' => $data['payrolls'],
            'employees' => $data['employees'],
            'cycleEmployees' => $data['cycleEmployees'],
            'summary' => $data['summary'],
        ]);
    }

    public function exportCsv(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $rows = $this->payrollExportRows($filters);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Employee', 'Client', 'Salary Period', 'Salary Date', 'Working Days', 'Payable Salary', 'Paid Salary', 'Remaining Due', 'Status', 'Payment Date', 'Method', 'Reference']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['employee'],
                    $row['client'],
                    $row['salary_period'],
                    $row['salary_date'],
                    $row['working_days'],
                    number_format($row['payable_salary'], 2, '.', ''),
                    number_format($row['paid_salary'], 2, '.', ''),
                    number_format($row['remaining_due'], 2, '.', ''),
                    $row['status'],
                    $row['payment_date'],
                    $row['method'],
                    $row['reference'],
                ]);
            }

            fclose($handle);
        }, 'salary-generate-report.csv', ['Content-Type' => 'text/csv']);
    }

    public function exportExcel(Request $request)
    {
        $filters = $this->validatedFilters($request);

        return response()->view('admin.payroll.export-excel', [
            'rows' => $this->payrollExportRows($filters),
        ], 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="salary-generate-report.xls"',
        ]);
    }

    public function create(ClientFundDashboardService $clientFundDashboardService)
    {
        $employees = Employee::orderBy('name')->get();
        $clients = Client::orderBy('company_name')->get();
        $clientBalances = $clientFundDashboardService->clientBalanceMap();
        $workStatusRecords = EmployeeWorkStatus::orderBy('work_date')
            ->get()
            ->map(fn (EmployeeWorkStatus $workStatus) => [
                'employee_id' => $workStatus->employee_id,
                'client_id' => $workStatus->client_id,
                'date' => $workStatus->work_date?->toDateString(),
                'status' => $workStatus->status,
                'status_label' => $workStatus->statusLabel(),
                'salary_count_value' => (float) $workStatus->salary_count_value,
                'note' => $workStatus->note,
            ])
            ->values();

        return view('admin.payroll.create', compact('employees', 'clients', 'clientBalances', 'workStatusRecords'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'client_id' => ['required', 'exists:clients,id'],
            'calculation_type' => ['required', Rule::in(['date_to_date', 'monthly_cycle'])],
            'salary_month' => ['nullable', 'required_if:calculation_type,monthly_cycle', 'date_format:Y-m'],
            'from_date' => ['nullable', 'required_if:calculation_type,date_to_date', 'date'],
            'to_date' => ['nullable', 'required_if:calculation_type,date_to_date', 'date', 'after_or_equal:from_date'],
            'use_work_status_records' => ['nullable', 'boolean'],
            'working_days' => ['nullable', 'numeric', 'min:0', 'max:31'],
            'non_working_days' => ['nullable', 'numeric', 'min:0', 'max:31'],
            'salary_day_adjustments' => ['nullable', 'array'],
            'salary_day_adjustments.*.date' => ['required_with:salary_day_adjustments', 'date'],
            'salary_day_adjustments.*.day_type' => ['required_with:salary_day_adjustments', Rule::in(['working', 'non_working'])],
            'salary_day_adjustments.*.salary_count_value' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'salary_day_adjustments.*.reason' => ['required_with:salary_day_adjustments', Rule::in([
                'active_working',
                'absent',
                'client_issue',
                'boosting_off',
                'business_closed',
                'agency_hold',
                'on_leave',
                'sick_leave',
                'holiday',
                'agency_closed',
                'other',
            ])],
            'salary_day_adjustments.*.note' => ['nullable', 'string', 'max:500'],
            'payment_status' => ['nullable', Rule::in(['upcoming', 'unpaid', 'partial', 'paid'])],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['nullable', 'date'],
            'payment_proof' => ['nullable', 'image', 'max:4096'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $calculation = $this->calculatePayroll($employee, $data);
        $paidAmount = (float) ($data['paid_amount'] ?? 0);
        $paymentStatus = EmployeePayroll::paymentStatusFor(null, $calculation['payable_salary'], $paidAmount);
        $this->validatePaymentWorkflow($request, $paidAmount);

        $payroll = EmployeePayroll::create([
            'employee_id' => $data['employee_id'],
            'client_id' => $data['client_id'],
            'calculation_type' => $data['calculation_type'],
            'salary_period_from' => $calculation['from_date']->toDateString(),
            'salary_period_to' => $calculation['to_date']->toDateString(),
            'from_date' => $calculation['from_date']->toDateString(),
            'to_date' => $calculation['to_date']->toDateString(),
            'working_days' => $calculation['working_days'],
            'non_working_days' => $calculation['non_working_days'],
            'month_days' => $calculation['month_days'],
            'daily_salary' => $calculation['daily_salary'],
            'salary_day_adjustments' => $calculation['salary_day_adjustments'],
            'salary_month' => $calculation['salary_month']->toDateString(),
            'payable_salary' => $calculation['payable_salary'],
            'paid_amount' => $paidAmount,
            'payment_method' => $data['payment_method'] ?? null,
            'payment_date' => $data['payment_date'] ?? null,
            'payment_status' => $paymentStatus,
            'payment_proof' => $request->file('payment_proof')?->store('employee-payroll-proofs', 'public'),
            'transaction_id' => $data['transaction_id'] ?? null,
            'status' => EmployeePayroll::statusFor($calculation['payable_salary'], $paidAmount),
            'note' => $data['note'] ?? null,
        ]);

        return redirect('/admin/payroll/' . $payroll->id)
            ->with('success', 'Employee payroll saved successfully.');
    }

    public function show($id)
    {
        $payroll = EmployeePayroll::with(['employee', 'client'])->findOrFail($id);

        return view('admin.payroll.show', compact('payroll'));
    }

    public function edit($id, ClientFundDashboardService $clientFundDashboardService)
    {
        $payroll = EmployeePayroll::with(['employee', 'client'])->findOrFail($id);
        $clientFundBalance = $payroll->client_id
            ? $clientFundDashboardService->clientAvailableBalance($payroll->client_id)
            : null;
        $clientFundNeed = max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0);

        return view('admin.payroll.edit', compact('payroll', 'clientFundBalance', 'clientFundNeed'));
    }

    public function update(Request $request, $id)
    {
        $payroll = EmployeePayroll::findOrFail($id);
        $data = $request->validate([
            'payment_status' => ['nullable', Rule::in(['upcoming', 'unpaid', 'partial', 'paid'])],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['nullable', 'date'],
            'payment_proof' => ['nullable', 'image', 'max:4096'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $paidAmount = (float) ($data['paid_amount'] ?? 0);
        $this->validatePaymentWorkflow($request, $paidAmount);

        $paymentProof = $payroll->payment_proof;

        if ($request->hasFile('payment_proof')) {
            if ($paymentProof) {
                Storage::disk('public')->delete($paymentProof);
            }

            $paymentProof = $request->file('payment_proof')->store('employee-payroll-proofs', 'public');
        }

        $payroll->update([
            'paid_amount' => $paidAmount,
            'payment_method' => $data['payment_method'] ?? null,
            'payment_date' => $data['payment_date'] ?? null,
            'payment_status' => EmployeePayroll::paymentStatusFor(null, (float) $payroll->payable_salary, $paidAmount),
            'payment_proof' => $paymentProof,
            'transaction_id' => $data['transaction_id'] ?? null,
            'status' => EmployeePayroll::statusFor((float) $payroll->payable_salary, $paidAmount),
            'note' => $data['note'] ?? null,
        ]);

        return redirect('/admin/payroll/' . $payroll->id)
            ->with('success', 'Employee payroll updated successfully.');
    }

    public function destroy(EmployeePayroll $payroll)
    {
        $payroll->delete();

        return redirect('/admin/payroll')
            ->with('success', 'Salary record deleted successfully.');
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'status' => ['nullable', 'in:upcoming,unpaid,partial,paid,due'],
        ]);
    }

    private function filteredPayrollData(array $filters): array
    {
        $query = EmployeePayroll::with(['employee', 'client'])
            ->when($filters['month'] ?? null, fn ($query, $month) => $query->whereDate('salary_month', $month . '-01'))
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId));

        $payrolls = $query->latest('salary_month')
            ->latest()
            ->get()
            ->filter(fn (EmployeePayroll $payroll) => $payroll->matchesStatusFilter($filters['status'] ?? null))
            ->values();

        $cycleEmployees = $this->cycleEmployeesForStatus($filters['status'] ?? null);

        return [
            'payrolls' => $payrolls,
            'employees' => Employee::orderBy('name')->get(),
            'cycleEmployees' => $cycleEmployees,
            'summary' => [
                'total_payable' => $payrolls->sum('payable_salary') + $cycleEmployees->sum(fn (Employee $employee) => (float) $employee->monthly_salary),
                'total_paid' => $payrolls->sum('paid_amount'),
                'total_due' => $payrolls->sum(fn (EmployeePayroll $payroll) => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0))
                    + $cycleEmployees->sum(fn (Employee $employee) => (float) $employee->monthly_salary),
                'record_count' => $payrolls->count() + $cycleEmployees->count(),
            ],
        ];
    }

    private function payrollExportRows(array $filters)
    {
        $data = $this->filteredPayrollData($filters);
        $statusLabels = [
            'upcoming' => 'Upcoming',
            'unpaid' => 'Unpaid',
            'partial' => 'Partially Paid',
            'paid' => 'Paid',
        ];

        $rows = $data['payrolls']->map(function (EmployeePayroll $payroll) use ($statusLabels) {
            return [
                'employee' => trim(($payroll->employee?->employee_id ?: '-') . ' ' . ($payroll->employee?->name ?: '')),
                'client' => $payroll->client?->company_name ?: '-',
                'salary_period' => $payroll->salary_period,
                'salary_date' => $payroll->employee?->salaryDateForMonth($payroll->salary_month?->copy() ?: now())?->toDateString() ?: '-',
                'working_days' => $payroll->working_days ?? 0,
                'payable_salary' => (float) $payroll->payable_salary,
                'paid_salary' => (float) $payroll->paid_amount,
                'remaining_due' => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0),
                'status' => $statusLabels[$payroll->calculated_status] ?? ucfirst($payroll->calculated_status),
                'payment_date' => $payroll->payment_date?->toDateString() ?: '-',
                'method' => $payroll->payment_method ?: '-',
                'reference' => $payroll->transaction_id ?: '-',
            ];
        });

        return $rows
            ->concat($data['cycleEmployees']->map(function (Employee $employee) use ($filters) {
                $salaryDate = ($filters['status'] ?? null) === 'due'
                    ? $employee->currentSalaryDueDate()
                    : $employee->nextSalaryDate();

                return [
                    'employee' => trim(($employee->employee_id ?: '-') . ' ' . $employee->name),
                    'client' => $employee->activeAssignments->first()?->client?->company_name ?: '-',
                    'salary_period' => $salaryDate?->format('Y-m') ?: '-',
                    'salary_date' => $salaryDate?->toDateString() ?: '-',
                    'working_days' => $salaryDate?->daysInMonth ?: 0,
                    'payable_salary' => (float) $employee->monthly_salary,
                    'paid_salary' => 0,
                    'remaining_due' => (float) $employee->monthly_salary,
                    'status' => $employee->salaryStatusLabel(),
                    'payment_date' => '-',
                    'method' => '-',
                    'reference' => '-',
                ];
            }))
            ->values();
    }

    private function calculatePayroll(Employee $employee, array $data): array
    {
        $submittedWorkingDays = $data['working_days'] ?? null;
        $submittedNonWorkingDays = $data['non_working_days'] ?? null;

        if ($data['calculation_type'] === 'monthly_cycle') {
            if (empty($data['salary_month'])) {
                abort(422, 'Salary month is required for monthly cycle salary.');
            }

            $salaryMonth = Carbon::createFromFormat('Y-m', $data['salary_month'])->startOfMonth();
            $fromDate = $salaryMonth->copy();
            $toDate = $salaryMonth->copy()->endOfMonth();
            $workingDays = $submittedWorkingDays !== null
                ? (float) $submittedWorkingDays
                : $fromDate->daysInMonth;
            $nonWorkingDays = $submittedNonWorkingDays !== null
                ? (float) $submittedNonWorkingDays
                : 0;
        } else {
            if (empty($data['from_date']) || empty($data['to_date'])) {
                abort(422, 'From Date and To Date are required for Date To Date salary.');
            }

            $fromDate = Carbon::parse($data['from_date']);
            $toDate = Carbon::parse($data['to_date']);
            $salaryMonth = $fromDate->copy()->startOfMonth();
            $adjustments = ! empty($data['use_work_status_records'])
                ? $this->workStatusAdjustments($employee, (int) ($data['client_id'] ?? 0), $fromDate, $toDate)
                : $this->normalizeSalaryDayAdjustments($data['salary_day_adjustments'] ?? [], $fromDate, $toDate);

            if ($adjustments !== []) {
                $nonWorkingDays = collect($adjustments)
                    ->where('day_type', 'non_working')
                    ->count();
                $workingDays = collect($adjustments)->contains(fn (array $adjustment) => array_key_exists('salary_count_value', $adjustment))
                    ? (float) collect($adjustments)->sum('salary_count_value')
                    : count($adjustments) - $nonWorkingDays;
            } else {
                $workingDays = $submittedWorkingDays !== null
                    ? (float) $submittedWorkingDays
                    : ((int) $fromDate->diffInDays($toDate)) + 1;
                $nonWorkingDays = (float) ($submittedNonWorkingDays ?? 0);
            }
        }

        $monthDays = $fromDate->daysInMonth;
        $monthlySalary = (float) $employee->monthly_salary;
        $dailySalary = round($monthlySalary / $monthDays, 2);
        $payableSalary = round(($monthlySalary * $workingDays) / $monthDays, 2);

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'salary_month' => $salaryMonth,
            'working_days' => $workingDays,
            'non_working_days' => $nonWorkingDays,
            'month_days' => $monthDays,
            'daily_salary' => $dailySalary,
            'salary_day_adjustments' => $adjustments ?? null,
            'payable_salary' => $payableSalary,
        ];
    }

    private function workStatusAdjustments(Employee $employee, int $clientId, Carbon $fromDate, Carbon $toDate): array
    {
        $workStatusByDate = $employee->workStatuses()
            ->whereDate('work_date', '>=', $fromDate->toDateString())
            ->whereDate('work_date', '<=', $toDate->toDateString())
            ->where(function ($query) use ($clientId) {
                $query->whereNull('client_id')
                    ->orWhere('client_id', $clientId);
            })
            ->get()
            ->keyBy(fn (EmployeeWorkStatus $workStatus) => $workStatus->work_date?->toDateString());

        $adjustments = [];
        $current = $fromDate->copy();

        while ($current->lte($toDate)) {
            $date = $current->toDateString();
            $workStatus = $workStatusByDate->get($date);
            $salaryCount = $workStatus ? (float) $workStatus->salary_count_value : 0.0;

            $adjustments[] = [
                'date' => $date,
                'day_type' => $salaryCount > 0 ? 'working' : 'non_working',
                'salary_count_value' => $salaryCount,
                'reason' => $workStatus ? $this->workStatusReason($workStatus->status) : 'other',
                'note' => $workStatus?->note ?: ($workStatus ? 'From work status record' : 'No work status record'),
            ];

            $current->addDay();
        }

        return $adjustments;
    }

    private function workStatusReason(string $status): string
    {
        return [
            'working' => 'active_working',
            'half_day' => 'active_working',
            'absent' => 'absent',
            'on_leave' => 'on_leave',
            'client_issue' => 'client_issue',
            'boosting_off' => 'boosting_off',
            'sick_leave' => 'sick_leave',
            'agency_closed' => 'agency_closed',
        ][$status] ?? 'other';
    }

    private function normalizeSalaryDayAdjustments(array $adjustments, Carbon $fromDate, Carbon $toDate): array
    {
        if ($adjustments === []) {
            return [];
        }

        $allowedReasons = [
            'active_working',
            'absent',
            'client_issue',
            'boosting_off',
            'business_closed',
            'agency_hold',
            'on_leave',
            'sick_leave',
            'holiday',
            'agency_closed',
            'other',
        ];
        $normalized = [];

        foreach ($adjustments as $adjustment) {
            if (empty($adjustment['date'])) {
                continue;
            }

            $date = Carbon::parse($adjustment['date'])->startOfDay();

            if ($date->lt($fromDate->copy()->startOfDay()) || $date->gt($toDate->copy()->startOfDay())) {
                continue;
            }

            $dayType = ($adjustment['day_type'] ?? 'working') === 'non_working'
                ? 'non_working'
                : 'working';
            $reason = in_array($adjustment['reason'] ?? 'active_working', $allowedReasons, true)
                ? $adjustment['reason']
                : 'active_working';

            $normalized[$date->toDateString()] = [
                'date' => $date->toDateString(),
                'day_type' => $dayType,
                'salary_count_value' => array_key_exists('salary_count_value', $adjustment)
                    ? (float) $adjustment['salary_count_value']
                    : ($dayType === 'working' ? 1.0 : 0.0),
                'reason' => $reason,
                'note' => trim((string) ($adjustment['note'] ?? '')),
            ];
        }

        ksort($normalized);

        return array_values($normalized);
    }

    private function validatePaymentWorkflow(Request $request, float $paidAmount): void
    {
        if ($paidAmount <= 0) {
            return;
        }

        $request->validate([
            'paid_amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'string', 'max:255'],
            'payment_date' => ['required', 'date'],
        ]);

    }

    private function cycleEmployeesForStatus(?string $status)
    {
        if (! in_array($status, ['upcoming', 'due'], true)) {
            return collect();
        }

        $today = now()->startOfDay();

        return Employee::with(['payrolls', 'activeAssignments.client'])
            ->orderBy('name')
            ->get()
            ->filter(function (Employee $employee) use ($status, $today) {
                if ($employee->status === 'terminated') {
                    return false;
                }

                $cycleDate = $status === 'upcoming'
                    ? $employee->nextSalaryDate()
                    : $employee->currentSalaryDueDate($today);

                if (! $cycleDate) {
                    return false;
                }

                $cycleMonth = $cycleDate->copy()->startOfMonth()->toDateString();
                $hasGeneratedSalary = $employee->payrolls->contains(
                    fn (EmployeePayroll $payroll) => $payroll->salary_month?->copy()->startOfMonth()->toDateString() === $cycleMonth
                );

                if ($hasGeneratedSalary) {
                    return false;
                }

                if ($status === 'upcoming') {
                    return $cycleDate->betweenIncluded($today, $today->copy()->addDays(5));
                }

                return $cycleDate->lt($today);
            })
            ->values();
    }

}
