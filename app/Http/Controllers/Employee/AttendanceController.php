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
        $employee = $this->employee()->load(['shift', 'assignments.shift']);
        $attendances = $employee->attendances()
            ->with(['client', 'shift'])
            ->latest('attendance_date')
            ->paginate(20);
        $todayAttendance = $employee->attendances()
            ->with('shift')
            ->whereDate('attendance_date', today())
            ->first();
        $monthlyAttendances = $employee->attendances()
            ->whereDate('attendance_date', '>=', now()->startOfMonth()->toDateString())
            ->whereDate('attendance_date', '<=', now()->endOfMonth()->toDateString())
            ->get();
        $primaryAssignment = $employee->assignments->where('status', 'active')->sortByDesc('assigned_from')->first();
        $summary = [
            'present_days' => $monthlyAttendances->where('status', 'present')->count(),
            'late_days' => $monthlyAttendances->where('is_late', true)->count(),
            'records' => $monthlyAttendances->count(),
        ];

        return view('employee.attendance.index', compact('employee', 'attendances', 'todayAttendance', 'primaryAssignment', 'summary'));
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

        $assignment = $this->activeAssignment($employee, $date);
        $shift = $assignment?->shift ?: $employee->shift;

        $attendance->fill([
            'client_id' => $assignment?->client_id,
            'shift_id' => $shift?->id,
            'status' => $data['status'],
            'check_in_at' => $data['status'] === 'present' ? now() : null,
            'is_late' => $data['status'] === 'present' ? $this->isLate($shift) : false,
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

        $assignment = $this->activeAssignment($employee, $today);
        $shift = $assignment?->shift ?: $employee->shift;

        $attendance->fill([
            'client_id' => $assignment?->client_id,
            'shift_id' => $shift?->id,
            'status' => 'present',
            'is_working_day' => true,
            'check_in_at' => now(),
            'is_late' => $this->isLate($shift),
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

    private function activeAssignment(Employee $employee, string $date): ?EmployeeAssignment
    {
        return EmployeeAssignment::where('employee_id', $employee->id)
            ->with('shift')
            ->where('status', 'active')
            ->whereDate('assigned_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('assigned_to')
                    ->orWhereDate('assigned_to', '>=', $date);
            })
            ->latest('assigned_from')
            ->first();
    }

    private function isLate($shift): bool
    {
        if (! $shift?->start_time) {
            return false;
        }

        $start = Carbon::parse(today()->toDateString() . ' ' . $shift->start_time->format('H:i:s'));

        return now()->gt($start);
    }
}
