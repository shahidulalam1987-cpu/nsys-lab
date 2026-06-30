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
    public function index()
    {
        $targets = EmployeeTarget::with(['employee', 'department', 'role'])->latest()->get();

        return view('admin.performance-targets.index', [
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
