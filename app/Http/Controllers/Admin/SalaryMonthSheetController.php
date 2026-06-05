<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalaryMonthSheetController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
        ]);

        $month = Carbon::createFromFormat('Y-m', $filters['month'] ?? now()->format('Y-m'))->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();
        $daysInMonth = $month->daysInMonth;

        $assignments = EmployeeAssignment::with([
            'client',
            'employee.salaryDays' => function ($query) use ($month, $monthEnd) {
                $query->whereBetween('date', [$month->toDateString(), $monthEnd->toDateString()]);
            },
        ])
            ->whereDate('assigned_from', '<=', $monthEnd->toDateString())
            ->where(function ($query) use ($month) {
                $query->whereNull('assigned_to')
                    ->orWhereDate('assigned_to', '>=', $month->toDateString());
            })
            ->when($filters['client_id'] ?? null, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->orderBy('client_id')
            ->orderBy('employee_id')
            ->get();

        $rows = $assignments
            ->map(function (EmployeeAssignment $assignment) use ($daysInMonth) {
                $salaryDays = $assignment->employee->salaryDays
                    ->where('client_id', $assignment->client_id);
                $countedDays = $salaryDays->where('is_counted', true)->count();
                $nonCountedDays = $salaryDays->where('is_counted', false)->count();
                $monthlySalary = (float) $assignment->employee->monthly_salary;
                $payableSalary = ($monthlySalary / $daysInMonth) * $countedDays;

                return [
                    'client' => $assignment->client,
                    'employee' => $assignment->employee,
                    'monthly_salary' => $monthlySalary,
                    'counted_days' => $countedDays,
                    'non_counted_days' => $nonCountedDays,
                    'payable_salary' => $payableSalary,
                    'salary_status' => $countedDays > 0 ? 'Payable' : 'No Counted Days',
                ];
            });

        $clients = Client::orderBy('company_name')->get();
        $employees = Employee::orderBy('name')->get();

        return view('admin.salary-month-sheet.index', [
            'filters' => $filters,
            'month' => $month,
            'clients' => $clients,
            'employees' => $employees,
            'rows' => $rows,
            'summary' => [
                'total_employees' => $rows->pluck('employee.id')->unique()->count(),
                'total_payable_salary' => $rows->sum('payable_salary'),
                'total_counted_days' => $rows->sum('counted_days'),
                'total_non_counted_days' => $rows->sum('non_counted_days'),
            ],
        ]);
    }
}
