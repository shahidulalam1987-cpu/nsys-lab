<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\EmployeeAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index()
    {
        $employee = $this->employee();
        $attendances = $employee->attendances()
            ->with('client')
            ->latest('attendance_date')
            ->paginate(20);

        return view('employee.attendance.index', compact('employee', 'attendances'));
    }

    public function store(Request $request)
    {
        $employee = $this->employee();
        $data = $request->validate([
            'attendance_date' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(EmployeeAttendance::STATUSES))],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $date = Carbon::parse($data['attendance_date'])->toDateString();
        $attendance = $employee->attendances()
            ->whereDate('attendance_date', $date)
            ->first() ?: $employee->attendances()->make(['attendance_date' => $date]);

        if ($attendance->exists && $attendance->check_in_at) {
            return back()->withErrors(['attendance_date' => 'Attendance already exists for this date.']);
        }

        $attendance->fill([
            'client_id' => $this->activeClientId($employee, $date),
            'status' => $data['status'],
            'check_in_at' => $data['status'] === 'present' ? now() : null,
            'note' => $data['note'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ])->save();

        return back()->with('success', 'Attendance saved successfully.');
    }

    public function checkIn()
    {
        $employee = $this->employee();
        $today = today()->toDateString();
        $attendance = $employee->attendances()
            ->whereDate('attendance_date', $today)
            ->first() ?: $employee->attendances()->make(['attendance_date' => $today]);

        if ($attendance->exists && $attendance->check_in_at) {
            return back()->withErrors(['attendance' => 'You have already checked in today.']);
        }

        $attendance->fill([
            'client_id' => $this->activeClientId($employee, $today),
            'status' => 'present',
            'is_working_day' => true,
            'check_in_at' => now(),
            'created_by' => $attendance->created_by ?: auth()->id(),
            'updated_by' => auth()->id(),
        ])->save();

        return back()->with('success', 'Checked in successfully.');
    }

    public function checkOut()
    {
        $employee = $this->employee();
        $attendance = $employee->attendances()
            ->whereDate('attendance_date', today())
            ->first();

        if (! $attendance || ! $attendance->check_in_at) {
            return back()->withErrors(['attendance' => 'Please check in before checking out.']);
        }

        if ($attendance->check_out_at) {
            return back()->withErrors(['attendance' => 'You have already checked out today.']);
        }

        $attendance->update([
            'check_out_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Checked out successfully.');
    }

    private function employee(): Employee
    {
        return auth()->user()->employee()->firstOrFail();
    }

    private function activeClientId(Employee $employee, string $date): ?int
    {
        return EmployeeAssignment::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->whereDate('assigned_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('assigned_to')
                    ->orWhereDate('assigned_to', '>=', $date);
            })
            ->latest('assigned_from')
            ->value('client_id');
    }
}
