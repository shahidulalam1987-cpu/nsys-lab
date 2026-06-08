<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $employee = auth()->user()
            ->employee()
            ->with(['assignments.client', 'salaryDays.client', 'payrolls.client', 'attendances.client', 'workStatuses.client'])
            ->firstOrFail();

        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $monthlyWorkStatuses = $employee->workStatuses
            ->filter(fn ($workStatus) => $workStatus->work_date?->toDateString() >= $monthStart
                && $workStatus->work_date?->toDateString() <= $monthEnd);
        $todayWorkStatus = $employee->workStatuses
            ->first(fn ($workStatus) => $workStatus->work_date?->toDateString() === today()->toDateString());
        $todayAttendance = $employee->attendances
            ->first(fn ($attendance) => $attendance->attendance_date?->toDateString() === today()->toDateString());
        $workStatusSummary = [
            'working_days' => (float) $monthlyWorkStatuses->sum('salary_count_value'),
            'half_days' => $monthlyWorkStatuses->where('status', 'half_day')->count(),
            'leave' => $monthlyWorkStatuses->where('status', 'on_leave')->count(),
            'client_issue' => $monthlyWorkStatuses->where('status', 'client_issue')->count(),
            'boosting_off' => $monthlyWorkStatuses->where('status', 'boosting_off')->count(),
        ];
        $activeAssignments = $employee->assignments->where('status', 'active');
        $payrolls = $employee->payrolls->sortByDesc('salary_month');

        return view('employee.dashboard', compact(
            'employee',
            'workStatusSummary',
            'todayWorkStatus',
            'todayAttendance',
            'activeAssignments',
            'payrolls'
        ));
    }
}
