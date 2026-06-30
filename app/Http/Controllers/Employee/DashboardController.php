<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeeNotice;
use App\Models\EmployeeNoticeRead;
use App\Services\AssignmentResolver;

class DashboardController extends Controller
{
    public function index()
    {
        $employee = auth()->user()
            ->employee()
            ->with([
                'shift',
                'assignments.client',
                'assignments.page',
                'assignments.shift',
                'payrolls.client',
                'attendances.client',
                'attendances.shift',
                'workStatuses.client',
                'workStatuses.page',
                'workStatuses.shift',
            ])
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
        $primaryAssignment = app(AssignmentResolver::class)->current($employee);
        $activeAssignments = $primaryAssignment ? collect([$primaryAssignment]) : collect();
        $payrolls = $employee->payrolls->sortByDesc('salary_month');
        $currentPayrolls = $payrolls->filter(fn ($payroll) => $payroll->is_current);
        $salarySummary = [
            'generated_salary' => (float) $currentPayrolls->sum('payable_salary'),
            'paid_salary' => (float) $currentPayrolls->sum('paid_amount'),
            'due_salary' => $currentPayrolls->sum(fn ($payroll) => max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0)),
        ];
        $latestNotices = EmployeeNotice::latest('published_at')->latest()->take(5)->get();
        $unreadNoticeCount = EmployeeNotice::whereDoesntHave('reads', function ($query) use ($employee) {
            $query->where('employee_id', $employee->id);
        })->count();
        $pendingWorkStatusCount = $employee->assignments
            ->where('status', 'active')
            ->count() > 0 && ! $todayWorkStatus ? 1 : 0;
        $submissionAlerts = [
            'pending' => $employee->dailySubmissions()->where('status', 'pending')->count(),
            'approved' => $employee->dailySubmissions()->whereIn('status', ['approved', 'merged'])->count(),
            'rejected' => $employee->dailySubmissions()->where('status', 'rejected')->count(),
        ];

        return view('employee.dashboard', compact(
            'employee',
            'workStatusSummary',
            'todayWorkStatus',
            'todayAttendance',
            'activeAssignments',
            'primaryAssignment',
            'payrolls',
            'salarySummary',
            'latestNotices',
            'unreadNoticeCount',
            'pendingWorkStatusCount',
            'submissionAlerts'
        ));
    }
}
