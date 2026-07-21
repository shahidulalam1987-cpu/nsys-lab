<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\Shift;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $query = $this->filteredQuery($filters);
        $attendances = $query->latest('attendance_date')->paginate(25)->withQueryString();
        $summaryQuery = $this->filteredQuery($filters);
        $summary = [
            'records' => (clone $summaryQuery)->count(),
            'present' => (clone $summaryQuery)->where('status', 'present')->count(),
            'absent' => (clone $summaryQuery)->where('status', 'absent')->count(),
            'on_leave' => (clone $summaryQuery)->where('status', 'on_leave')->count(),
            'client_issue' => (clone $summaryQuery)->where('status', 'client_issue')->count(),
            'boosting_off' => (clone $summaryQuery)->where('status', 'boosting_off')->count(),
            'late' => (clone $summaryQuery)->where('is_late', true)->count(),
        ];

        return view('admin.attendance.index', [
            'attendances' => $attendances,
            'employees' => Employee::orderBy('name')->get(),
            'clients' => Client::orderBy('company_name')->get(),
            'shifts' => Shift::orderBy('name')->get(),
            'statuses' => EmployeeAttendance::STATUSES,
            'filters' => $filters,
            'summary' => $summary,
        ]);
    }

    public function edit(EmployeeAttendance $attendance)
    {
        $attendance->load(['employee', 'client', 'shift']);

        return view('admin.attendance.edit', [
            'attendance' => $attendance,
            'clients' => Client::orderBy('company_name')->get(),
            'statuses' => EmployeeAttendance::STATUSES,
        ]);
    }

    public function update(Request $request, EmployeeAttendance $attendance)
    {
        $data = $request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'status' => ['required', Rule::in(array_keys(EmployeeAttendance::STATUSES))],
            'is_working_day' => ['nullable', 'boolean'],
            'is_late' => ['nullable', 'boolean'],
            'check_in_at' => ['nullable', 'date'],
            'check_out_at' => ['nullable', 'date', 'after_or_equal:check_in_at'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $attendance->fill([
            'client_id' => $data['client_id'] ?? null,
            'status' => $data['status'],
            'check_in_at' => $data['check_in_at'] ?? null,
            'check_out_at' => $data['check_out_at'] ?? null,
            'note' => $data['note'] ?? null,
            'updated_by' => auth()->id(),
        ]);

        if ($request->has('is_working_day')) {
            $attendance->is_working_day = (bool) $data['is_working_day'];
        }

        if ($request->has('is_late')) {
            $attendance->is_late = (bool) $data['is_late'];
        }

        $attendance->save();

        app(ActivityLogger::class)->log(
            'Attendance',
            'Attendance Updated',
            'Attendance #' . $attendance->id . ' updated for ' . ($attendance->employee?->employee_id ?: 'employee #' . $attendance->employee_id) . '.',
            $request
        );

        return redirect('/admin/attendance')->with('success', 'Attendance updated successfully.');
    }

    public function destroy(EmployeeAttendance $attendance)
    {
        $description = 'Attendance #' . $attendance->id . ' deleted for ' . ($attendance->employee?->employee_id ?: 'employee #' . $attendance->employee_id) . '.';
        $attendance->delete();

        app(ActivityLogger::class)->log('Attendance', 'Attendance Deleted', $description, request());

        return redirect('/admin/attendance')->with('success', 'Attendance record deleted successfully.');
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request);
        $rows = $this->filteredQuery($filters)->latest('attendance_date')->get();

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Employee', 'Client', 'Shift', 'Check In', 'Check Out', 'Late', 'Status', 'Day Type', 'Note']);

            foreach ($rows as $attendance) {
                fputcsv($handle, [
                    $attendance->attendance_date?->toDateString(),
                    trim(($attendance->employee?->employee_id ?: '-') . ' ' . ($attendance->employee?->name ?: '')),
                    $attendance->client?->company_name ?: '-',
                    $attendance->shift?->name ?: '-',
                    $attendance->check_in_at?->format('Y-m-d H:i') ?: '-',
                    $attendance->check_out_at?->format('Y-m-d H:i') ?: '-',
                    $attendance->is_late ? 'Yes' : 'No',
                    $attendance->statusLabel(),
                    $attendance->is_working_day ? 'Working Day' : 'Non Working Day',
                    $attendance->note ?: '-',
                ]);
            }

            fclose($handle);
        }, 'employee-attendance-report.csv', ['Content-Type' => 'text/csv']);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['nullable', 'exists:employees,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', Rule::in(array_keys(EmployeeAttendance::STATUSES))],
            'late_status' => ['nullable', Rule::in(['late', 'on_time'])],
        ]);
    }

    private function filteredQuery(array $filters)
    {
        return EmployeeAttendance::with(['employee', 'client', 'shift'])
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when($filters['client_id'] ?? null, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->when($filters['shift_id'] ?? null, fn ($query, $shiftId) => $query->where('shift_id', $shiftId))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('attendance_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('attendance_date', '<=', $date))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['late_status'] ?? null, fn ($query, $lateStatus) => $query->where('is_late', $lateStatus === 'late'));
    }
}
