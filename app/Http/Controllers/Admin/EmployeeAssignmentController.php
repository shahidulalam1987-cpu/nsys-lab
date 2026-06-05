<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use Illuminate\Http\Request;

class EmployeeAssignmentController extends Controller
{
    public function store(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'assigned_from' => ['required', 'date'],
            'assigned_to' => ['nullable', 'date', 'after_or_equal:assigned_from'],
            'status' => ['required', 'in:active,ended'],
            'note' => ['nullable', 'string'],
        ]);

        $hasOverlap = $employee->assignments()
            ->whereDate('assigned_from', '<=', $data['assigned_to'] ?: '9999-12-31')
            ->where(function ($query) use ($data) {
                $query->whereNull('assigned_to')
                    ->orWhereDate('assigned_to', '>=', $data['assigned_from']);
            })
            ->exists();

        $employee->assignments()->create($data);

        $message = $hasOverlap
            ? 'Assignment saved. Warning: this employee has overlapping assignment dates.'
            : 'Assignment saved successfully.';

        return back()->with('success', $message);
    }

    public function update(Request $request, EmployeeAssignment $assignment)
    {
        $data = $request->validate([
            'assigned_to' => ['nullable', 'date', 'after_or_equal:' . $assignment->assigned_from->toDateString()],
            'status' => ['required', 'in:active,ended'],
            'note' => ['nullable', 'string'],
        ]);

        $assignment->update($data);

        return back()->with('success', 'Assignment updated successfully.');
    }

    public function destroy(EmployeeAssignment $assignment)
    {
        $employeeId = $assignment->employee_id;

        $assignment->delete();

        return redirect('/admin/employees/' . $employeeId)
            ->with('success', 'Assignment deleted successfully.');
    }
}
