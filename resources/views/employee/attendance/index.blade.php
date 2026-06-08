@extends('layouts.employee')

@section('content')
    <h1>My Attendance</h1>
    <p>Attendance is for shift monitoring only. Salary is calculated from Work Status records assigned by admin or team leader.</p>

    @if ($errors->any())
        <div class="card" style="color:#ef4444;">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

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
                    <th>Client</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Status</th>
                    <th>Day Type</th>
                    <th>Note</th>
                </tr>
                @forelse($attendances as $attendance)
                    <tr>
                        <td>{{ $attendance->attendance_date?->toDateString() }}</td>
                        <td>{{ $attendance->client?->company_name ?: '-' }}</td>
                        <td>{{ $attendance->check_in_at?->format('h:i A') ?: '-' }}</td>
                        <td>{{ $attendance->check_out_at?->format('h:i A') ?: '-' }}</td>
                        <td>{{ $attendance->statusLabel() }}</td>
                        <td>{{ $attendance->is_working_day ? 'Working Day' : 'Non Working Day' }}</td>
                        <td>{{ $attendance->note ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">No attendance records found.</td></tr>
                @endforelse
            </table>
        </div>
        {{ $attendances->links() }}
    </div>
@endsection
