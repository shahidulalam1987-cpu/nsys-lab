<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeePayroll;
use Carbon\Carbon;
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
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'working_days' => ['required', 'integer', 'min:0', 'max:31'],
            'non_working_days' => ['required', 'integer', 'min:0', 'max:31'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $fromDate = Carbon::parse($data['from_date']);
        $monthDays = $fromDate->daysInMonth;
        $dailySalary = (float) $employee->monthly_salary / $monthDays;
        $payableSalary = $dailySalary * (int) $data['working_days'];
        $paidAmount = (float) $data['paid_amount'];

        $payroll = EmployeePayroll::create([
            'employee_id' => $data['employee_id'],
            'client_id' => $data['client_id'],
            'from_date' => $fromDate->toDateString(),
            'to_date' => Carbon::parse($data['to_date'])->toDateString(),
            'working_days' => (int) $data['working_days'],
            'non_working_days' => (int) $data['non_working_days'],
            'salary_month' => $fromDate->copy()->startOfMonth()->toDateString(),
            'payable_salary' => $payableSalary,
            'paid_amount' => $paidAmount,
            'payment_method' => $data['payment_method'] ?? null,
            'payment_date' => $data['payment_date'] ?? null,
            'status' => EmployeePayroll::statusFor($payableSalary, $paidAmount),
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
            'status' => EmployeePayroll::statusFor((float) $payroll->payable_salary, $paidAmount),
            'note' => $data['note'] ?? null,
        ]);

        return redirect('/admin/payroll/' . $payroll->id)
            ->with('success', 'Employee payroll updated successfully.');
    }

}
