@extends('layouts.admin')

@section('content')
    <h1>Employee Management</h1>

    <a class="btn" href="/admin/employees/create">Add Employee</a>

    <div class="stats-grid">
        <div class="stat-card"><p>Total Employees</p><h2>{{ number_format($summary['total']) }}</h2></div>
        <div class="stat-card"><p>Active</p><h2>{{ number_format($summary['active']) }}</h2></div>
        <div class="stat-card"><p>Probation</p><h2>{{ number_format($summary['probation']) }}</h2></div>
        <div class="stat-card"><p>On Leave</p><h2>{{ number_format($summary['on_leave']) }}</h2></div>
        <div class="stat-card"><p>Inactive</p><h2>{{ number_format($summary['inactive']) }}</h2></div>
        <div class="stat-card"><p>Terminated</p><h2>{{ number_format($summary['terminated']) }}</h2></div>
    </div>

    <div class="card" style="margin-top:20px;">
        <form method="GET" action="/admin/employees">
            <input type="text" name="search" placeholder="Employee ID, name, mobile" value="{{ request('search') }}">
            <select name="status">
                <option value="">All Employees</option>
                @foreach(\App\Models\Employee::STATUS_FILTERS as $value => $label)
                    <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Search</button>
            <a href="/admin/employees">Reset</a>
        </form>
        <p>Total Employees Found: {{ $employees->count() }}</p>
        <p>
            <a href="/admin/employees">All Employees</a>
            @foreach(\App\Models\Employee::STATUS_FILTERS as $value => $label)
                | <a href="/admin/employees?status={{ $value }}">{{ $label }}</a>
            @endforeach
        </p>
    </div>

    <div class="card">
        <table>
            <tr>
                <th>Employee ID</th>
                <th>Name</th>
                <th>Mobile</th>
                <th>Department</th>
                <th>Role</th>
                <th>Joining Date</th>
                <th>Salary</th>
                <th>Status</th>
                <th>Login Status</th>
                <th>Confirmation</th>
                <th>Actions</th>
            </tr>
            @forelse($employees as $employee)
                <tr>
                    <td><a href="/admin/employees/{{ $employee->id }}">{{ $employee->employee_id }}</a></td>
                    <td>{{ $employee->name }}</td>
                    <td>{{ $employee->mobile ?: '-' }}</td>
                    <td>{{ $employee->department }}</td>
                    <td>{{ $employee->role }}</td>
                    <td>{{ $employee->joining_date?->toDateString() }}</td>
                    <td>BDT {{ number_format($employee->monthly_salary, 2) }}</td>
                    <td>{{ $employee->statusLabel() }}</td>
                    <td>
                        @if($employee->user_id)
                            <span class="badge badge-success">Linked</span>
                        @else
                            <span class="badge badge-warning">Not Linked</span>
                        @endif
                    </td>
                    <td>
                        @if($employee->confirmation_date)
                            {{ $employee->confirmation_date->toDateString() }}
                        @elseif($employee->isEligibleForConfirmation())
                            <span class="badge badge-warning">Eligible</span>
                        @else
                            Pending
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="/admin/employees/{{ $employee->id }}/terminate" style="display:inline;">
                            @csrf
                            <button class="btn-danger" type="submit" onclick="return confirm('Terminate this employee? History and login will be preserved.');">Deactivate / Terminate</button>
                        </form>

                        <form method="POST" action="/admin/employees/{{ $employee->id }}/delete" style="display:inline;">
                            @csrf
                            <button class="btn-danger" type="submit" onclick="return confirm('Delete this employee? This is allowed only when no history exists.');">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="11">No employees found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
