<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeWorkStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeWorkStatusController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $rows = $this->filteredQuery($filters)->latest('work_date')->paginate(25)->withQueryString();
        $summaryRows = $this->filteredQuery($filters)->get();

        return view('admin.work-status.index', [
            'workStatuses' => $rows,
            'employees' => Employee::orderBy('name')->get(),
            'clients' => Client::orderBy('company_name')->get(),
            'statuses' => EmployeeWorkStatus::STATUSES,
            'filters' => $filters,
            'summary' => [
                'working_days' => (float) $summaryRows->sum('salary_count_value'),
                'half_days' => $summaryRows->where('status', 'half_day')->count(),
                'leave' => $summaryRows->where('status', 'on_leave')->count(),
                'client_issue' => $summaryRows->where('status', 'client_issue')->count(),
                'boosting_off' => $summaryRows->where('status', 'boosting_off')->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.work-status.create', [
            'employees' => Employee::orderBy('name')->get(),
            'clients' => Client::orderBy('company_name')->get(),
            'statuses' => EmployeeWorkStatus::STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $workStatus = EmployeeWorkStatus::updateOrCreate([
            'employee_id' => $data['employee_id'],
            'work_date' => $data['work_date'],
        ], [
            'client_id' => $data['client_id'] ?? null,
            'status' => $data['status'],
            'salary_count_value' => EmployeeWorkStatus::salaryCountFor($data['status']),
            'note' => $data['note'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect('/admin/work-status/' . $workStatus->id . '/edit')
            ->with('success', 'Work status saved successfully.');
    }

    public function edit(EmployeeWorkStatus $workStatus)
    {
        return view('admin.work-status.edit', [
            'workStatus' => $workStatus->load(['employee', 'client']),
            'employees' => Employee::orderBy('name')->get(),
            'clients' => Client::orderBy('company_name')->get(),
            'statuses' => EmployeeWorkStatus::STATUSES,
        ]);
    }

    public function update(Request $request, EmployeeWorkStatus $workStatus)
    {
        $data = $request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'status' => ['required', Rule::in(array_keys(EmployeeWorkStatus::STATUSES))],
            'salary_count_value' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $workStatus->fill([
            'client_id' => $data['client_id'] ?? null,
            'status' => $data['status'],
            'salary_count_value' => array_key_exists('salary_count_value', $data)
                ? (float) $data['salary_count_value']
                : EmployeeWorkStatus::salaryCountFor($data['status']),
            'note' => $data['note'] ?? null,
            'updated_by' => auth()->id(),
        ])->save();

        return redirect('/admin/work-status')->with('success', 'Work status updated successfully.');
    }

    public function destroy(EmployeeWorkStatus $workStatus)
    {
        $workStatus->delete();

        return redirect('/admin/work-status')->with('success', 'Work status deleted successfully.');
    }

    public function export(Request $request)
    {
        $rows = $this->filteredQuery($this->filters($request))
            ->latest('work_date')
            ->get();

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Employee', 'Client', 'Status', 'Salary Count Value', 'Note']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->work_date?->toDateString(),
                    trim(($row->employee?->employee_id ?: '-') . ' ' . ($row->employee?->name ?: '')),
                    $row->client?->company_name ?: '-',
                    $row->statusLabel(),
                    number_format((float) $row->salary_count_value, 2, '.', ''),
                    $row->note ?: '-',
                ]);
            }

            fclose($handle);
        }, 'employee-work-status-report.csv', ['Content-Type' => 'text/csv']);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['nullable', 'exists:employees,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', Rule::in(array_keys(EmployeeWorkStatus::STATUSES))],
        ]);
    }

    private function filteredQuery(array $filters)
    {
        return EmployeeWorkStatus::with(['employee', 'client'])
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when($filters['client_id'] ?? null, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('work_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('work_date', '<=', $date))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'work_date' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(EmployeeWorkStatus::STATUSES))],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
