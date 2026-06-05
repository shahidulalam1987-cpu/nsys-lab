@extends('layouts.admin')

@section('content')
    <h1>Salary Month Sheet</h1>

    <div class="card">
        <form method="GET" action="/admin/salary-month-sheet">
            <input type="month" name="month" value="{{ request('month', $month->format('Y-m')) }}">

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
            <a class="btn" href="/admin/salary-month-sheet/export?{{ http_build_query(request()->only(['month', 'employee_id'])) }}">Export CSV</a>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <p>Total Employees</p>
            <h2>{{ number_format($summary['total_employees']) }}</h2>
        </div>
        <div class="stat-card">
            <p>Total Working Days</p>
            <h2>{{ number_format($summary['total_counted_days']) }}</h2>
        </div>
        <div class="stat-card">
            <p>Total Payable Salary (BDT)</p>
            <h2>BDT {{ number_format($summary['total_payable_salary'], 2) }}</h2>
        </div>
    </div>

    <div class="card">
        <h2>{{ $month->format('F Y') }}</h2>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>Employee ID</th>
                    <th>Employee Name</th>
                    <th>Month</th>
                    <th>Working Days</th>
                    <th>Non Working Days</th>
                    <th>Monthly Salary</th>
                    <th>Payable Salary (BDT)</th>
                </tr>

                @forelse($rows as $row)
                    <tr>
                        <td>
                            <a href="/admin/employees/{{ $row['employee']->id }}">
                                {{ $row['employee']->employee_id }}
                            </a>
                        </td>
                        <td>{{ $row['employee']->name }}</td>
                        <td>{{ $row['month']->format('Y-m') }}</td>
                        <td>{{ $row['counted_days'] }}</td>
                        <td>{{ $row['non_counted_days'] }}</td>
                        <td>BDT {{ number_format($row['monthly_salary'], 2) }}</td>
                        <td>BDT {{ number_format($row['payable_salary'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">No salary day records found for this month.</td>
                    </tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
