@extends('layouts.admin')

@section('content')
    <h1>Employee Management</h1>

    <a class="btn" href="/admin/employees/create">Add Employee</a>

    <div class="card" style="margin-top:20px;">
        <form method="GET" action="/admin/employees">
            <input type="text" name="search" placeholder="Employee ID, name, mobile" value="{{ request('search') }}">
            <select name="status">
                <option value="">All Status</option>
                @foreach(['probation' => 'Probation', 'active' => 'Active', 'on_leave' => 'On Leave', 'suspended' => 'Suspended', 'terminated' => 'Terminated'] as $value => $label)
                    <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Search</button>
            <a href="/admin/employees">Reset</a>
        </form>
        <p>Total Employees Found: {{ $employees->count() }}</p>
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
                <th>Confirmation</th>
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
                    <td>{{ ucwords(str_replace('_', ' ', $employee->status)) }}</td>
                    <td>
                        @if($employee->confirmation_date)
                            {{ $employee->confirmation_date->toDateString() }}
                        @elseif($employee->isEligibleForConfirmation())
                            <span class="badge badge-warning">Eligible</span>
                        @else
                            Pending
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">No employees found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
