@extends('layouts.employee')

@section('content')
    <h1>My Attendance</h1>
    <p>Attendance is for shift monitoring only. Salary is calculated from Work Status records assigned by admin or team leader.</p>

    <div class="stats-grid">
        <div class="stat-card"><p>Today's Shift</p><h2>{{ $todayAttendance?->shift?->name ?: $primaryAssignment?->shift?->name ?: $employee->shift?->name ?: '-' }}</h2></div>
        <div class="stat-card"><p>Today's Status</p><h2>{{ $todayAttendance?->statusLabel() ?: 'Not Marked' }}</h2></div>
        <div class="stat-card"><p>Present Days</p><h2>{{ number_format($summary['present_days']) }}</h2></div>
        <div class="stat-card"><p>Late Days</p><h2>{{ number_format($summary['late_days']) }}</h2></div>
        <div class="stat-card"><p>Attendance Records</p><h2>{{ number_format($summary['records']) }}</h2></div>
    </div>

    <div class="card">
        <h2>Mark Attendance</h2>
        <form method="POST" action="/employee/attendance">
            @csrf
            <input type="date" name="attendance_date" value="{{ now()->toDateString() }}" required>
            <select name="status" required>
                @foreach(\App\Models\EmployeeAttendance::STATUSES as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <input type="text" name="note" placeholder="Note">
            <button class="btn" type="submit">Save Attendance</button>
        </form>
    </div>

    <div class="card">
        <h2>Attendance History</h2>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Status</th>
                </tr>
                @forelse($attendances as $attendance)
                    <tr>
                        <td>{{ $attendance->attendance_date?->toDateString() }}</td>
                        <td>{{ $attendance->check_in_at?->format('h:i A') ?: '-' }}</td>
                        <td>{{ $attendance->check_out_at?->format('h:i A') ?: '-' }}</td>
                        <td>{{ $attendance->statusLabel() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No attendance records found.</td></tr>
                @endforelse
            </table>
        </div>
        {{ $attendances->links() }}
    </div>
@endsection
