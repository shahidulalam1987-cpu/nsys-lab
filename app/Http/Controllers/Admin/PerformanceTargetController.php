<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeRole;
use App\Models\EmployeeTarget;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PerformanceTargetController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'scope' => ['nullable', Rule::in(['employee', 'role', 'department'])],
            'target_type' => ['nullable', Rule::in(['orders', 'spend', 'max_cpo', 'approval_rate'])],
            'period_type' => ['nullable', Rule::in(['daily', 'weekly', 'monthly'])],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
        $baseQuery = EmployeeTarget::query();
        $summary = [
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'employee' => (clone $baseQuery)->whereNotNull('employee_id')->count(),
            'role' => (clone $baseQuery)->whereNotNull('role_id')->count(),
            'department' => (clone $baseQuery)->whereNotNull('department_id')->count(),
        ];
        $targets = EmployeeTarget::with(['employee', 'department', 'role'])
            ->when($filters['scope'] ?? null, function ($query, string $scope) {
                return match ($scope) {
                    'employee' => $query->whereNotNull('employee_id'),
                    'role' => $query->whereNotNull('role_id')->whereNull('employee_id'),
                    'department' => $query->whereNotNull('department_id')->whereNull('employee_id')->whereNull('role_id'),
                };
            })
            ->when($filters['target_type'] ?? null, fn ($query, $type) => $query->where('target_type', $type))
            ->when($filters['period_type'] ?? null, fn ($query, $period) => $query->where('period_type', $period))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->get();

        return view('admin.performance-targets.index', [
            'filters' => $filters,
            'summary' => $summary,
            'targets' => $targets, 'employees' => Employee::orderBy('name')->get(),
            'departments' => Department::ordered()->get(), 'roles' => EmployeeRole::ordered()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['nullable', 'exists:employees,id'], 'department_id' => ['nullable', 'exists:departments,id'],
            'role_id' => ['nullable', 'exists:employee_roles,id'],
            'target_type' => ['required', Rule::in(['orders', 'spend', 'max_cpo', 'approval_rate'])],
            'target_value' => ['required', 'numeric', 'min:0'], 'period_type' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'start_date' => ['required', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
        if (empty($data['employee_id']) && empty($data['role_id']) && empty($data['department_id'])) {
            throw ValidationException::withMessages(['employee_id' => 'Select an employee, role, or department target scope.']);
        }
        $data['created_by'] = auth()->id();
        EmployeeTarget::create($data);

        return back()->with('success', 'Performance target saved.');
    }

    public function destroy(EmployeeTarget $target)
    {
        $target->delete();

        return back()->with('success', 'Performance target deleted.');
    }
}
