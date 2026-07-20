<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\EmployeeRole;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeRoleController extends Controller
{
    public function index()
    {
        $roles = EmployeeRole::with('department')->withCount('employees')->ordered()->get();
        $summary = [
            'total' => $roles->count(),
            'active' => $roles->where('status', 'active')->count(),
            'inactive' => $roles->where('status', 'inactive')->count(),
            'assigned_employees' => $roles->sum('employees_count'),
            'department_linked' => $roles->whereNotNull('department_id')->count(),
        ];

        return view('admin.employee-roles.index', compact('roles', 'summary'));
    }

    public function create()
    {
        $departments = Department::active()->ordered()->get();

        return view('admin.employee-roles.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        $role = EmployeeRole::create($data);

        app(ActivityLogger::class)->log('Employee', 'Employee Role Created', $role->name, $request);

        return redirect('/admin/employee-roles')->with('success', 'Role created successfully.');
    }

    public function edit(EmployeeRole $employeeRole)
    {
        $departments = Department::active()
            ->when($employeeRole->department_id, fn ($query) => $query->orWhere('id', $employeeRole->department_id))
            ->ordered()
            ->get();

        return view('admin.employee-roles.edit', compact('employeeRole', 'departments'));
    }

    public function update(Request $request, EmployeeRole $employeeRole)
    {
        $data = $this->validated($request, $employeeRole);
        $data['slug'] = $this->uniqueSlug($data['name'], $employeeRole);
        $data['updated_by'] = auth()->id();
        $employeeRole->update($data);

        app(ActivityLogger::class)->log('Employee', 'Employee Role Updated', $employeeRole->name, $request);

        return redirect('/admin/employee-roles')->with('success', 'Role updated successfully.');
    }

    public function destroy(EmployeeRole $employeeRole)
    {
        if ($employeeRole->employees()->exists()) {
            return redirect('/admin/employee-roles')->withErrors([
                'role' => 'This role has employees and cannot be deleted. Set inactive instead.',
            ]);
        }

        $name = $employeeRole->name;
        $employeeRole->delete();
        app(ActivityLogger::class)->log('Employee', 'Employee Role Deleted', $name, request());

        return redirect('/admin/employee-roles')->with('success', 'Role deleted successfully.');
    }

    private function validated(Request $request, ?EmployeeRole $role = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('employee_roles', 'name')->ignore($role?->id)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $duplicate = EmployeeRole::withTrashed()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['name']))])
            ->when($role, fn ($query) => $query->where('id', '!=', $role->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['name' => 'An employee role with this name already exists.']);
        }

        $data['name'] = trim($data['name']);

        return $data;
    }

    private function uniqueSlug(string $name, ?EmployeeRole $role = null): string
    {
        $base = Str::slug($name) ?: 'employee-role';
        $slug = $base;
        $suffix = 2;

        while (EmployeeRole::withTrashed()
            ->where('slug', $slug)
            ->when($role, fn ($query) => $query->where('id', '!=', $role->id))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
