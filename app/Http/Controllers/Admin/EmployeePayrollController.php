<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeePayroll;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployeePayrollController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'status' => ['nullable', 'in:upcoming,unpaid,partial,paid'],
        ]);

        $query = EmployeePayroll::with(['employee', 'client'])
            ->when($filters['month'] ?? null, fn ($query, $month) => $query->whereDate('salary_month', $month . '-01'))
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->withCalculatedStatus($filters['status'] ?? null);

        $payrolls = $query->latest('salary_month')->latest()->get();
        $employees = Employee::orderBy('name')->get();

        return view('admin.payroll.index', [
            'filters' => $filters,
            'payrolls' => $payrolls,
            'employees' => $employees,
            'summary' => [
                'total_payable' => $payrolls->sum('payable_salary'),
                'total_paid' => $payrolls->sum('paid_amount'),
                'total_due' => $payrolls->sum(fn (EmployeePayroll $payroll) => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0)),
            ],
        ]);
    }

    public function create()
    {
        $employees = Employee::orderBy('name')->get();
        $clients = Client::orderBy('company_name')->get();

        return view('admin.payroll.create', compact('employees', 'clients'));
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
            'working_days' => ['nullable', 'integer', 'min:0', 'max:31'],
            'non_working_days' => ['nullable', 'integer', 'min:0', 'max:31'],
            'salary_day_adjustments' => ['nullable', 'array'],
            'salary_day_adjustments.*.date' => ['required_with:salary_day_adjustments', 'date'],
            'salary_day_adjustments.*.day_type' => ['required_with:salary_day_adjustments', Rule::in(['working', 'non_working'])],
            'salary_day_adjustments.*.reason' => ['required_with:salary_day_adjustments', Rule::in([
                'active_working',
                'client_issue',
                'boosting_off',
                'business_closed',
                'agency_hold',
                'on_leave',
                'sick_leave',
                'other',
            ])],
            'salary_day_adjustments.*.note' => ['nullable', 'string', 'max:500'],
            'payment_status' => ['nullable', Rule::in(['upcoming', 'unpaid', 'partial', 'paid'])],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'required_if:payment_status,partial,paid', 'string', 'max:255'],
            'payment_date' => ['nullable', 'required_if:payment_status,partial,paid', 'date'],
            'payment_proof' => ['nullable', 'image', 'max:4096'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $calculation = $this->calculatePayroll($employee, $data);
        $paidAmount = (float) ($data['paid_amount'] ?? 0);
        $requestedPaymentStatus = $data['payment_status'] ?? EmployeePayroll::statusFor($calculation['payable_salary'], $paidAmount);
        $paymentStatus = EmployeePayroll::paymentStatusFor($requestedPaymentStatus, $calculation['payable_salary'], $paidAmount);

        if (array_key_exists('payment_status', $data)) {
            $this->validatePaymentWorkflow($request, $requestedPaymentStatus, $paidAmount);
        }

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

    public function edit($id)
    {
        $payroll = EmployeePayroll::with(['employee', 'client'])->findOrFail($id);

        return view('admin.payroll.edit', compact('payroll'));
    }

    public function update(Request $request, $id)
    {
        $payroll = EmployeePayroll::findOrFail($id);
        $data = $request->validate([
            'payment_status' => ['required', Rule::in(['upcoming', 'unpaid', 'partial', 'paid'])],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'required_if:payment_status,partial,paid', 'string', 'max:255'],
            'payment_date' => ['nullable', 'required_if:payment_status,partial,paid', 'date'],
            'payment_proof' => ['nullable', 'image', 'max:4096'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $paidAmount = (float) ($data['paid_amount'] ?? 0);
        $this->validatePaymentWorkflow($request, $data['payment_status'], $paidAmount);

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
            'payment_status' => EmployeePayroll::paymentStatusFor($data['payment_status'], (float) $payroll->payable_salary, $paidAmount),
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
                ? (int) $submittedWorkingDays
                : $fromDate->daysInMonth;
            $nonWorkingDays = $submittedNonWorkingDays !== null
                ? (int) $submittedNonWorkingDays
                : 0;
        } else {
            if (empty($data['from_date']) || empty($data['to_date'])) {
                abort(422, 'From Date and To Date are required for Date To Date salary.');
            }

            $fromDate = Carbon::parse($data['from_date']);
            $toDate = Carbon::parse($data['to_date']);
            $salaryMonth = $fromDate->copy()->startOfMonth();
            $adjustments = $this->normalizeSalaryDayAdjustments($data['salary_day_adjustments'] ?? [], $fromDate, $toDate);

            if ($adjustments !== []) {
                $nonWorkingDays = collect($adjustments)
                    ->where('day_type', 'non_working')
                    ->count();
                $workingDays = count($adjustments) - $nonWorkingDays;
            } else {
                $workingDays = $submittedWorkingDays !== null
                    ? (int) $submittedWorkingDays
                    : ((int) $fromDate->diffInDays($toDate)) + 1;
                $nonWorkingDays = (int) ($submittedNonWorkingDays ?? 0);
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

    private function normalizeSalaryDayAdjustments(array $adjustments, Carbon $fromDate, Carbon $toDate): array
    {
        if ($adjustments === []) {
            return [];
        }

        $allowedReasons = [
            'active_working',
            'client_issue',
            'boosting_off',
            'business_closed',
            'agency_hold',
            'on_leave',
            'sick_leave',
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
                'reason' => $reason,
                'note' => trim((string) ($adjustment['note'] ?? '')),
            ];
        }

        ksort($normalized);

        return array_values($normalized);
    }

    private function validatePaymentWorkflow(Request $request, string $paymentStatus, float $paidAmount): void
    {
        if (! in_array($paymentStatus, ['partial', 'paid'], true)) {
            return;
        }

        $request->validate([
            'paid_amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'string', 'max:255'],
            'payment_date' => ['required', 'date'],
        ]);

        if ($paidAmount <= 0) {
            abort(422, 'Paid Salary is required for paid salary status.');
        }
    }

}
