<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $employee = auth()->user()
            ->employee()
            ->with(['assignments.client', 'salaryDays.client', 'payrolls.client'])
            ->firstOrFail();

        $countedDays = $employee->salaryDays->where('is_counted', true)->count();
        $nonCountedDays = $employee->salaryDays->where('is_counted', false)->count();
        $activeAssignments = $employee->assignments->where('status', 'active');
        $payrolls = $employee->payrolls->sortByDesc('salary_month');

        return view('employee.dashboard', compact(
            'employee',
            'countedDays',
            'nonCountedDays',
            'activeAssignments',
            'payrolls'
        ));
    }
}
