<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeePayroll;
use App\Services\SalaryMonthSheetService;
use Illuminate\Http\Request;

class EmployeePayrollController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'status' => ['nullable', 'in:unpaid,partial,paid'],
        ]);

        $query = EmployeePayroll::with(['employee', 'client'])
            ->when($filters['month'] ?? null, fn ($query, $month) => $query->whereDate('salary_month', $month . '-01'))
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));

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

    public function create(Request $request, SalaryMonthSheetService $salaryMonthSheetService)
    {
        $filters = $request->validate([
            'employee_id' => ['nullable', 'exists:employees,id'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $employees = Employee::orderBy('name')->get();
        $selectedEmployee = isset($filters['employee_id'])
            ? Employee::find($filters['employee_id'])
            : null;
        $selectedMonth = $filters['month'] ?? now()->format('Y-m');
        $payable = $selectedEmployee
            ? $salaryMonthSheetService->employeePayable($selectedEmployee->id, $selectedMonth)
            : null;

        return view('admin.payroll.create', compact(
            'employees',
            'selectedEmployee',
            'selectedMonth',
            'payable'
        ));
    }

    public function store(Request $request, SalaryMonthSheetService $salaryMonthSheetService)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'salary_month' => ['required', 'date_format:Y-m'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ]);

        $payable = $salaryMonthSheetService->employeePayable($data['employee_id'], $data['salary_month']);
        $paidAmount = (float) $data['paid_amount'];

        $payroll = EmployeePayroll::create([
            'employee_id' => $data['employee_id'],
            'client_id' => $payable['client_id'],
            'salary_month' => $payable['month']->toDateString(),
            'payable_salary' => $payable['payable_salary'],
            'paid_amount' => $paidAmount,
            'payment_method' => $data['payment_method'] ?? null,
            'payment_date' => $data['payment_date'] ?? null,
            'status' => $this->statusFor($payable['payable_salary'], $paidAmount),
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
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ]);

        $paidAmount = (float) $data['paid_amount'];

        $payroll->update([
            'paid_amount' => $paidAmount,
            'payment_method' => $data['payment_method'] ?? null,
            'payment_date' => $data['payment_date'] ?? null,
            'status' => $this->statusFor((float) $payroll->payable_salary, $paidAmount),
            'note' => $data['note'] ?? null,
        ]);

        return redirect('/admin/payroll/' . $payroll->id)
            ->with('success', 'Employee payroll updated successfully.');
    }

    private function statusFor(float $payableSalary, float $paidAmount): string
    {
        if ($paidAmount <= 0) {
            return 'unpaid';
        }

        if ($paidAmount < $payableSalary) {
            return 'partial';
        }

        return 'paid';
    }
}
