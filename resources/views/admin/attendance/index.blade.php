@extends('layouts.admin')

@section('content')
    <style>
        .attendance-filter-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            align-items: end;
        }

        .attendance-filter-grid label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        .attendance-filter-grid input,
        .attendance-filter-grid select {
            margin: 6px 0 0;
            width: 100%;
        }

        .attendance-filter-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .attendance-muted {
            color: var(--muted);
            margin-top: 0;
        }
    </style>

    <h1>Attendance</h1>
    <p>Attendance is for check-in, check-out, shift monitoring, and late tracking only. Salary is calculated from Work Status records.</p>

    <div class="stats-grid">
        <div class="stat-card"><p>Total Records</p><h2>{{ number_format($summary['records']) }}</h2></div>
        <div class="stat-card"><p>Total Present</p><h2>{{ number_format($summary['present']) }}</h2></div>
        <div class="stat-card"><p>Total Absent</p><h2>{{ number_format($summary['absent']) }}</h2></div>
        <div class="stat-card"><p>Total Leave</p><h2>{{ number_format($summary['on_leave']) }}</h2></div>
        <div class="stat-card"><p>Total Client Issue</p><h2>{{ number_format($summary['client_issue']) }}</h2></div>
        <div class="stat-card"><p>Total Boosting OFF</p><h2>{{ number_format($summary['boosting_off']) }}</h2></div>
        <div class="stat-card"><p>Late Records</p><h2>{{ number_format($summary['late']) }}</h2></div>
    </div>

    <div class="card">
        <form method="GET" action="/admin/attendance" class="attendance-filter-grid">
            <label>
                Employee
                <select name="employee_id">
                    <option value="">All Employees</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ ($filters['employee_id'] ?? '') == $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }} ({{ $employee->employee_id }})
                        </option>
                    @endforeach
                </select>
            </label>
            <label>
                Client
                <select name="client_id">
                    <option value="">All Clients</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ ($filters['client_id'] ?? '') == $client->id ? 'selected' : '' }}>
                            {{ $client->company_name }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label>
                Shift
                <select name="shift_id">
                    <option value="">All Shifts</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}" {{ ($filters['shift_id'] ?? '') == $shift->id ? 'selected' : '' }}>
                            {{ $shift->name }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label>
                From
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </label>
            <label>
                To
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </label>
            <label>
                Status
                <select name="status">
                    <option value="">All Status</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Late Tracking
                <select name="late_status">
                    <option value="">All Records</option>
                    <option value="late" {{ ($filters['late_status'] ?? '') === 'late' ? 'selected' : '' }}>Late</option>
                    <option value="on_time" {{ ($filters['late_status'] ?? '') === 'on_time' ? 'selected' : '' }}>On Time</option>
                </select>
            </label>
            <div class="attendance-filter-actions">
                <button class="btn" type="submit">Filter</button>
                <a href="/admin/attendance">Reset</a>
                <a class="btn" href="/admin/attendance/export?{{ http_build_query($filters) }}">Export CSV</a>
            </div>
        </form>
    </div>

    <div class="card">
        <p class="attendance-muted">Showing {{ number_format($attendances->total()) }} attendance records.</p>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Client</th>
                    <th>Shift</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Late</th>
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
                        <td>{{ $attendance->shift?->name ?: '-' }}</td>
                        <td>{{ $attendance->check_in_at?->format('h:i A') ?: '-' }}</td>
                        <td>{{ $attendance->check_out_at?->format('h:i A') ?: '-' }}</td>
                        <td>{{ $attendance->is_late ? 'Yes' : 'No' }}</td>
                        <td>{{ $attendance->statusLabel() }}</td>
                        <td>{{ $attendance->is_working_day ? 'Working Day' : 'Non Working Day' }}</td>
                        <td>{{ $attendance->note ?: '-' }}</td>
                        <td>
                            <a href="/admin/attendance/{{ $attendance->id }}/edit">Edit</a>
                            |
                            <form method="POST" action="/admin/attendance/{{ $attendance->id }}/delete" style="display:inline;">
                                @csrf
                                <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this monitoring attendance record?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11">No attendance records found.</td></tr>
                @endforelse
            </table>
        </div>
        {{ $attendances->links() }}
    </div>
@endsection
