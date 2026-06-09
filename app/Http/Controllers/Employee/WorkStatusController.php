<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeeWorkStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkStatusController extends Controller
{
    public function index(Request $request)
    {
        $employee = auth()->user()->employee()->firstOrFail();
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', Rule::in(array_keys(EmployeeWorkStatus::STATUSES))],
        ]);

        $month = $filters['month'] ?? now()->format('Y-m');
        $monthDate = Carbon::createFromFormat('Y-m', $month);
        $query = $employee->workStatuses()
            ->with(['client', 'page', 'shift'])
            ->whereDate('work_date', '>=', $month . '-01')
            ->whereDate('work_date', '<=', $monthDate->copy()->endOfMonth()->toDateString())
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));

        $workStatuses = $query->latest('work_date')->get();

        return view('employee.work-status', [
            'employee' => $employee,
            'workStatuses' => $workStatuses,
            'statuses' => EmployeeWorkStatus::STATUSES,
            'filters' => $filters + ['month' => $month],
            'summary' => [
                'working_days' => (float) $workStatuses->sum('salary_count_value'),
                'half_days' => $workStatuses->where('status', 'half_day')->count(),
                'leave' => $workStatuses->where('status', 'on_leave')->count(),
                'client_issue' => $workStatuses->where('status', 'client_issue')->count(),
                'boosting_off' => $workStatuses->where('status', 'boosting_off')->count(),
            ],
        ]);
    }
}
