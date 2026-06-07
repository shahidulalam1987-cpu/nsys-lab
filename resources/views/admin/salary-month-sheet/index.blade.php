@extends('layouts.admin')

@section('content')
    <h1>Salary Report</h1>

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

            <select name="status">
                <option value="">All Status</option>
                @foreach(['upcoming' => 'Upcoming', 'unpaid' => 'Unpaid', 'partial' => 'Partially Paid', 'paid' => 'Paid'] as $value => $label)
                    <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <button class="btn" type="submit">Filter</button>
            <a href="/admin/salary-month-sheet">Reset</a>
            <a class="btn" href="/admin/salary-month-sheet/export?{{ http_build_query(request()->only(['month', 'employee_id', 'status'])) }}">Export CSV</a>
            <a class="btn" href="/admin/salary-month-sheet/export/excel?{{ http_build_query(request()->only(['month', 'employee_id', 'status'])) }}">Export Excel</a>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <p>Total Salary Records</p>
            <h2>{{ number_format($summary['total_salary_records']) }}</h2>
        </div>
        <div class="stat-card">
            <p>Total Payable Salary (BDT)</p>
            <h2>BDT {{ number_format($summary['total_payable_salary'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Total Paid Salary</p>
            <h2>BDT {{ number_format($summary['total_paid_salary'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Total Remaining Due</p>
            <h2>BDT {{ number_format($summary['total_remaining_due'], 2) }}</h2>
        </div>
    </div>

    <div class="card">
        <h2>{{ $month->format('F Y') }}</h2>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>Employee</th>
                    <th>Client</th>
                    <th>Salary Period</th>
                    <th>Working Days</th>
                    <th>Payable Salary (BDT)</th>
                    <th>Paid Salary</th>
                    <th>Remaining Due</th>
                    <th>Status</th>
                    <th>Payment Date</th>
                </tr>

                @forelse($rows as $payroll)
                    <tr>
                        <td>
                            @if($payroll->employee)
                                <a href="/admin/employees/{{ $payroll->employee->id }}">{{ $payroll->employee->employee_id }}</a>
                                <br>{{ $payroll->employee->name }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $payroll->client?->company_name ?: '-' }}</td>
                        <td>{{ $payroll->salary_period }}</td>
                        <td>{{ $payroll->working_days ?? '-' }}</td>
                        <td>BDT {{ number_format($payroll->payable_salary, 2) }}</td>
                        <td>BDT {{ number_format($payroll->paid_amount, 2) }}</td>
                        <td>BDT {{ number_format(max($payroll->payable_salary - $payroll->paid_amount, 0), 2) }}</td>
                        <td>{{ ['upcoming' => 'Upcoming', 'unpaid' => 'Unpaid', 'partial' => 'Partially Paid', 'paid' => 'Paid'][$payroll->calculated_status] ?? ucfirst($payroll->calculated_status) }}</td>
                        <td>{{ $payroll->payment_date?->toDateString() ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">No generated salary records found for this month.</td>
                    </tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
