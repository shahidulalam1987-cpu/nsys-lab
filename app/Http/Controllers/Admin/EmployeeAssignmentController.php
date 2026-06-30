<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientPage;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Shift;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmployeeAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $query = EmployeeAssignment::with(['employee', 'client', 'page', 'campaignRecord', 'shift'])
            ->when($request->employee_id, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when($request->client_id, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status));

        $assignments = $query->latest('assigned_from')->latest()->get();
        $allAssignments = EmployeeAssignment::with(['shift'])->get();

        return view('admin.assignments.index', [
            'assignments' => $assignments,
            'employees' => Employee::orderBy('name')->get(),
            'clients' => Client::orderBy('company_name')->get(),
            'summary' => [
                'total' => $allAssignments->count(),
                'active' => $allAssignments->where('status', 'active')->count(),
                'morning' => $allAssignments->where('status', 'active')->filter(fn ($assignment) => $assignment->shift?->name === 'Morning Shift')->count(),
                'night' => $allAssignments->where('status', 'active')->filter(fn ($assignment) => $assignment->shift?->name === 'Night Shift')->count(),
                'full_day' => $allAssignments->where('status', 'active')->filter(fn ($assignment) => $assignment->shift?->name === 'Full Day Shift')->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.assignments.create', $this->formData());
    }

    public function storeFromManagement(Request $request)
    {
        $employee = Employee::findOrFail($request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
        ])['employee_id']);

        if ($employee->isAgencyInternal() && ! $request->filled('client_id')) {
            return redirect('/admin/assignments')->with('success', 'Agency Internal employees do not require client/page assignment.');
        }

        $data = $this->validatedData($request, requirePage: ! $employee->isAgencyInternal(), employee: $employee);
        $this->ensureHierarchyMatches($data);
        $this->ensureNoDuplicateActivePageAssignment($data);

        $assignment = EmployeeAssignment::create($data);

        app(ActivityLogger::class)->log('Assignment', 'Assignment Created', 'Assignment #' . $assignment->id . ' created from Assignment Management.', $request);

        return redirect('/admin/assignments')->with('success', 'Assignment saved successfully.');
    }

    public function show(EmployeeAssignment $assignment)
    {
        return view('admin.assignments.show', [
            'assignment' => $assignment->load(['employee', 'client', 'page', 'campaignRecord', 'shift']),
        ]);
    }

    public function edit(EmployeeAssignment $assignment)
    {
        return view('admin.assignments.edit', array_merge($this->formData(), [
            'assignment' => $assignment->load(['employee', 'client', 'page', 'campaignRecord', 'shift']),
        ]));
    }

    public function updateFromManagement(Request $request, EmployeeAssignment $assignment)
    {
        $employee = Employee::findOrFail($request->input('employee_id', $assignment->employee_id));
        $data = $this->validatedData($request, requirePage: ! $employee->isAgencyInternal(), assignment: $assignment, employee: $employee);
        $this->ensureHierarchyMatches($data);
        $this->ensureNoDuplicateActivePageAssignment($data, $assignment);

        $assignment->update($data);

        app(ActivityLogger::class)->log('Assignment', 'Assignment Updated', 'Assignment #' . $assignment->id . ' updated from Assignment Management.', $request);

        return redirect('/admin/assignments')->with('success', 'Assignment updated successfully.');
    }

    public function store(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'client_page_id' => ['nullable', 'exists:client_pages,id'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
            'campaign' => ['nullable', 'string', 'max:255'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'assigned_from' => ['required', 'date'],
            'assigned_to' => ['nullable', 'date', 'after_or_equal:assigned_from'],
            'status' => ['required', 'in:active,ended'],
            'note' => ['nullable', 'string'],
        ]);
        $data['employee_id'] = $employee->id;

        $this->ensureHierarchyMatches($data);
        $this->ensureNoDuplicateActivePageAssignment($data);

        $assignedTo = $data['assigned_to'] ?? null;
        $hasOverlap = $employee->assignments()
            ->whereDate('assigned_from', '<=', $assignedTo ?: '9999-12-31')
            ->where(function ($query) use ($data) {
                $query->whereNull('assigned_to')
                    ->orWhereDate('assigned_to', '>=', $data['assigned_from']);
            })
            ->exists();

        $assignment = $employee->assignments()->create(collect($data)->except('employee_id')->all());

        app(ActivityLogger::class)->log('Assignment', 'Assignment Created', 'Assignment #' . $assignment->id . ' created for ' . $employee->name . '.', $request);

        $message = $hasOverlap
            ? 'Assignment saved. Warning: this employee has overlapping assignment dates.'
            : 'Assignment saved successfully.';

        return back()->with('success', $message);
    }

    public function update(Request $request, EmployeeAssignment $assignment)
    {
        $data = $request->validate([
            'client_page_id' => ['nullable', 'exists:client_pages,id'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
            'campaign' => ['nullable', 'string', 'max:255'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'assigned_to' => ['nullable', 'date', 'after_or_equal:' . $assignment->assigned_from->toDateString()],
            'status' => ['required', 'in:active,ended'],
            'note' => ['nullable', 'string'],
        ]);
        $data['employee_id'] = $assignment->employee_id;
        $data['client_id'] = $assignment->client_id;

        $this->ensureHierarchyMatches($data);
        $this->ensureNoDuplicateActivePageAssignment($data, $assignment);

        $assignment->update(collect($data)->except(['employee_id', 'client_id'])->all());

        app(ActivityLogger::class)->log('Assignment', 'Assignment Updated', 'Assignment #' . $assignment->id . ' updated.', $request);

        return back()->with('success', 'Assignment updated successfully.');
    }

    public function destroy(EmployeeAssignment $assignment)
    {
        $employeeId = $assignment->employee_id;
        $description = 'Assignment #' . $assignment->id . ' deleted from employee profile.';

        $assignment->delete();

        app(ActivityLogger::class)->log('Assignment', 'Assignment Deleted', $description, request());

        return redirect('/admin/employees/' . $employeeId)
            ->with('success', 'Assignment deleted successfully.');
    }

    public function remove(EmployeeAssignment $assignment)
    {
        $description = 'Assignment #' . $assignment->id . ' removed from Assignment Management.';
        $assignment->delete();

        app(ActivityLogger::class)->log('Assignment', 'Assignment Deleted', $description, request());

        return redirect('/admin/assignments')->with('success', 'Assignment removed successfully.');
    }

    private function formData(): array
    {
        return [
            'employees' => Employee::orderBy('name')->get(),
            'clients' => Client::orderBy('company_name')->get(),
            'clientPages' => ClientPage::with('client')->orderBy('page_name')->get(),
            'campaigns' => Campaign::with(['client', 'page'])->orderBy('campaign_name')->get(),
            'shifts' => Shift::where('status', 'active')->orderBy('id')->get(),
        ];
    }

    private function validatedData(Request $request, bool $requirePage, ?EmployeeAssignment $assignment = null, ?Employee $employee = null): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'client_id' => [$employee?->isAgencyInternal() ? 'nullable' : 'required', 'exists:clients,id'],
            'client_page_id' => [$requirePage ? 'required' : 'nullable', 'exists:client_pages,id'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
            'campaign' => ['nullable', 'string', 'max:255'],
            'shift_id' => [$employee?->isAgencyInternal() ? 'nullable' : 'required', 'exists:shifts,id'],
            'assigned_from' => ['required', 'date'],
            'assigned_to' => ['nullable', 'date', 'after_or_equal:assigned_from'],
            'status' => ['required', 'in:active,ended'],
            'note' => ['nullable', 'string'],
        ]);
    }

    private function ensureNoDuplicateActivePageAssignment(array $data, ?EmployeeAssignment $ignoreAssignment = null): void
    {
        if (($data['status'] ?? null) !== 'active' || empty($data['client_page_id'])) {
            return;
        }

        $exists = EmployeeAssignment::where('employee_id', $data['employee_id'])
            ->where('client_page_id', $data['client_page_id'])
            ->where('status', 'active')
            ->when($ignoreAssignment, fn ($query) => $query->whereKeyNot($ignoreAssignment->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'client_page_id' => 'This employee already has an active assignment for this page.',
            ]);
        }
    }

    private function ensureHierarchyMatches(array $data): void
    {
        $clientId = isset($data['client_id']) ? (int) $data['client_id'] : null;
        $page = ! empty($data['client_page_id']) ? ClientPage::find($data['client_page_id']) : null;
        $campaign = ! empty($data['campaign_id']) ? Campaign::find($data['campaign_id']) : null;

        if ($page && $clientId && (int) $page->client_id !== $clientId) {
            throw ValidationException::withMessages(['client_page_id' => 'Selected page does not belong to the selected client.']);
        }

        if ($campaign && $clientId && (int) $campaign->client_id !== $clientId) {
            throw ValidationException::withMessages(['campaign_id' => 'Selected campaign does not belong to the selected client.']);
        }

        if ($campaign && $page && (int) $campaign->client_page_id !== (int) $page->id) {
            throw ValidationException::withMessages(['campaign_id' => 'Selected campaign does not belong to the selected page.']);
        }
    }
}
