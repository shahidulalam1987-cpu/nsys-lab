@extends('layouts.admin')

@section('content')
    <h1>Salary Month Sheet</h1>

    <div class="card">
        <form method="GET" action="/admin/salary-month-sheet">
            <input type="month" name="month" value="{{ request('month', $month->format('Y-m')) }}">

            <select name="client_id">
                <option value="">All Clients</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                        {{ $client->company_name }}
                    </option>
                @endforeach
            </select>

            <select name="employee_id">
                <option value="">All Employees</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                        {{ $employee->name }} ({{ $employee->employee_id }})
                    </option>
                @endforeach
            </select>

            <button class="btn" type="submit">Filter</button>
            <a href="/admin/salary-month-sheet">Reset</a>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <p>Total Employees</p>
            <h2>{{ number_format($summary['total_employees']) }}</h2>
        </div>
        <div class="stat-card">
            <p>Total Payable Salary</p>
            <h2>BDT {{ number_format($summary['total_payable_salary'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Total Counted Days</p>
            <h2>{{ number_format($summary['total_counted_days']) }}</h2>
        </div>
        <div class="stat-card">
            <p>Total Non-Counted Days</p>
            <h2>{{ number_format($summary['total_non_counted_days']) }}</h2>
        </div>
    </div>

    <div class="card">
        <h2>{{ $month->format('F Y') }}</h2>

        <table>
            <tr>
                <th>Client</th>
                <th>Employee</th>
                <th>Monthly Salary</th>
                <th>Counted Days</th>
                <th>Non-Counted Days</th>
                <th>Payable Salary</th>
                <th>Salary Status</th>
            </tr>

            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['client']?->company_name }}</td>
                    <td>
                        <a href="/admin/employees/{{ $row['employee']->id }}">
                            {{ $row['employee']->name }}
                        </a>
                    </td>
                    <td>BDT {{ number_format($row['monthly_salary'], 2) }}</td>
                    <td>{{ $row['counted_days'] }}</td>
                    <td>{{ $row['non_counted_days'] }}</td>
                    <td>BDT {{ number_format($row['payable_salary'], 2) }}</td>
                    <td>
                        @if($row['salary_status'] === 'Payable')
                            <span class="badge badge-success">Payable</span>
                        @else
                            <span class="badge badge-warning">No Counted Days</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No client-employee assignments found for this month.</td>
                </tr>
            @endforelse
        </table>
    </div>
@endsection
