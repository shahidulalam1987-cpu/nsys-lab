<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::withCount([
            'employees',
            'employeeRoles',
            'employees as active_employees_count' => fn ($query) => $query->where('status', 'active'),
        ])->ordered()->get();
        $summary = [
            'total' => $departments->count(),
            'active' => $departments->where('status', 'active')->count(),
            'inactive' => $departments->where('status', 'inactive')->count(),
            'assigned_employees' => $departments->sum('employees_count'),
            'assigned_roles' => $departments->sum('employee_roles_count'),
        ];

        return view('admin.departments.index', compact('departments', 'summary'));
    }

    public function create()
    {
        return view('admin.departments.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        $department = Department::create($data);

        app(ActivityLogger::class)->log('Employee', 'Department Created', $department->name, $request);

        return redirect('/admin/departments')->with('success', 'Department created successfully.');
    }

    public function edit(Department $department)
    {
        return view('admin.departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
        $data = $this->validated($request, $department);
        $data['slug'] = $this->uniqueSlug($data['name'], $department);
        $data['updated_by'] = auth()->id();
        $department->update($data);

        app(ActivityLogger::class)->log('Employee', 'Department Updated', $department->name, $request);

        return redirect('/admin/departments')->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        if ($department->employees()->exists() || $department->employeeRoles()->exists()) {
            return redirect('/admin/departments')->withErrors([
                'department' => 'This department has employees or roles and cannot be deleted. Set inactive instead.',
            ]);
        }

        $name = $department->name;
        $department->delete();
        app(ActivityLogger::class)->log('Employee', 'Department Deleted', $name, request());

        return redirect('/admin/departments')->with('success', 'Department deleted successfully.');
    }

    private function validated(Request $request, ?Department $department = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($department?->id)],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $duplicate = Department::withTrashed()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['name']))])
            ->when($department, fn ($query) => $query->where('id', '!=', $department->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['name' => 'A department with this name already exists.']);
        }

        $data['name'] = trim($data['name']);

        return $data;
    }

    private function uniqueSlug(string $name, ?Department $department = null): string
    {
        $base = Str::slug($name) ?: 'department';
        $slug = $base;
        $suffix = 2;

        while (Department::withTrashed()
            ->where('slug', $slug)
            ->when($department, fn ($query) => $query->where('id', '!=', $department->id))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
