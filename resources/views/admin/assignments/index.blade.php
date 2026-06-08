@extends('layouts.admin')

@section('content')
    <h1>Assignment Management</h1>
    <p>Assign employees to client pages and shifts for daily operations.</p>

    <p><a class="btn" href="/admin/assignments/create">Create Assignment</a></p>

    <div class="card">
        <h2>Assignment Dashboard</h2>
        <div class="stats-grid" style="margin-bottom:0;">
            <div class="stat-card"><p>Total Assignments</p><h2>{{ number_format($summary['total']) }}</h2></div>
            <div class="stat-card"><p>Active Assignments</p><h2>{{ number_format($summary['active']) }}</h2></div>
            <div class="stat-card"><p>Morning Shift Employees</p><h2>{{ number_format($summary['morning']) }}</h2></div>
            <div class="stat-card"><p>Night Shift Employees</p><h2>{{ number_format($summary['night']) }}</h2></div>
            <div class="stat-card"><p>Full Day Employees</p><h2>{{ number_format($summary['full_day']) }}</h2></div>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="/admin/assignments">
            <select name="employee_id">
                <option value="">All Employees</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>{{ $employee->name }} ({{ $employee->employee_id }})</option>
                @endforeach
            </select>
            <select name="client_id">
                <option value="">All Clients</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>{{ $client->company_name }}</option>
                @endforeach
            </select>
            <select name="status">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="ended" {{ request('status') === 'ended' ? 'selected' : '' }}>Inactive</option>
            </select>
            <button class="btn" type="submit">Filter</button>
            <a href="/admin/assignments">Reset</a>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Employee</th>
                    <th>Client</th>
                    <th>Page Name</th>
                    <th>Shift</th>
                    <th>Assigned Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                @forelse($assignments as $assignment)
                    <tr>
                        <td>
                            <a href="/admin/employees/{{ $assignment->employee?->id }}">{{ $assignment->employee?->employee_id }}</a><br>
                            {{ $assignment->employee?->name }}
                        </td>
                        <td>{{ $assignment->client?->company_name ?: '-' }}</td>
                        <td>{{ $assignment->page?->page_name ?: '-' }}</td>
                        <td>{{ $assignment->shift?->name ?: '-' }}</td>
                        <td>{{ $assignment->assigned_from?->toDateString() ?: '-' }}</td>
                        <td>{{ $assignment->statusLabel() }}</td>
                        <td>
                            <a href="/admin/assignments/{{ $assignment->id }}">View</a>
                            |
                            <a href="/admin/assignments/{{ $assignment->id }}/edit">Edit</a>
                            |
                            <form method="POST" action="/admin/assignments/{{ $assignment->id }}/remove" style="display:inline;">
                                @csrf
                                <button class="btn btn-danger" type="submit" onclick="return confirm('Remove this assignment?');">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">No assignments found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
