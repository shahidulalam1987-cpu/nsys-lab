@extends('layouts.admin')

@section('content')
    <h1>Attendance</h1>
    <p>Attendance is for check-in, check-out, shift monitoring, and late tracking only. Salary is calculated from Work Status records.</p>

    <div class="stats-grid">
        <div class="stat-card"><p>Total Present</p><h2>{{ number_format($summary['present']) }}</h2></div>
        <div class="stat-card"><p>Total Absent</p><h2>{{ number_format($summary['absent']) }}</h2></div>
        <div class="stat-card"><p>Total Leave</p><h2>{{ number_format($summary['on_leave']) }}</h2></div>
        <div class="stat-card"><p>Total Client Issue</p><h2>{{ number_format($summary['client_issue']) }}</h2></div>
        <div class="stat-card"><p>Total Boosting OFF</p><h2>{{ number_format($summary['boosting_off']) }}</h2></div>
    </div>

    <div class="card">
        <form method="GET" action="/admin/attendance">
            <select name="employee_id">
                <option value="">All Employees</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ ($filters['employee_id'] ?? '') == $employee->id ? 'selected' : '' }}>
                        {{ $employee->name }} ({{ $employee->employee_id }})
                    </option>
                @endforeach
            </select>
            <select name="client_id">
                <option value="">All Clients</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ ($filters['client_id'] ?? '') == $client->id ? 'selected' : '' }}>
                        {{ $client->company_name }}
                    </option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            <select name="status">
                <option value="">All Status</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Filter</button>
            <a href="/admin/attendance">Reset</a>
            <a class="btn" href="/admin/attendance/export?{{ http_build_query($filters) }}">Export CSV</a>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Client</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Status</th>
                    <th>Working Day / Non Working Day</th>
                    <th>Note</th>
                    <th>Action</th>
                </tr>
                @forelse($attendances as $attendance)
                    <tr>
                        <td>{{ $attendance->attendance_date?->toDateString() }}</td>
                        <td>
                            <a href="/admin/employees/{{ $attendance->employee?->id }}">{{ $attendance->employee?->employee_id }}</a><br>
                            {{ $attendance->employee?->name }}
                        </td>
                        <td>{{ $attendance->client?->company_name ?: '-' }}</td>
                        <td>{{ $attendance->check_in_at?->format('h:i A') ?: '-' }}</td>
                        <td>{{ $attendance->check_out_at?->format('h:i A') ?: '-' }}</td>
                        <td>{{ $attendance->statusLabel() }}</td>
                        <td>{{ $attendance->is_working_day ? 'Working Day' : 'Non Working Day' }}</td>
                        <td>{{ $attendance->note ?: '-' }}</td>
                        <td>
                            <a href="/admin/attendance/{{ $attendance->id }}/edit">Edit</a>
                            |
                            <form method="POST" action="/admin/attendance/{{ $attendance->id }}/delete" style="display:inline;">
                                @csrf
                                <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this attendance record?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9">No attendance records found.</td></tr>
                @endforelse
            </table>
        </div>
        {{ $attendances->links() }}
    </div>
@endsection
