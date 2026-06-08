<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $employee = auth()->user()
            ->employee()
            ->with(['assignments.client', 'salaryDays.client', 'payrolls.client', 'attendances.client'])
            ->firstOrFail();

        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $monthlyAttendances = $employee->attendances
            ->filter(fn ($attendance) => $attendance->attendance_date?->toDateString() >= $monthStart
                && $attendance->attendance_date?->toDateString() <= $monthEnd);
        $todayAttendance = $employee->attendances
            ->first(fn ($attendance) => $attendance->attendance_date?->toDateString() === today()->toDateString());
        $countedDays = $monthlyAttendances->where('is_working_day', true)->count();
        $nonCountedDays = $monthlyAttendances->where('is_working_day', false)->count();
        $activeAssignments = $employee->assignments->where('status', 'active');
        $payrolls = $employee->payrolls->sortByDesc('salary_month');

        return view('employee.dashboard', compact(
            'employee',
            'countedDays',
            'nonCountedDays',
            'todayAttendance',
            'activeAssignments',
            'payrolls'
        ));
    }
}
