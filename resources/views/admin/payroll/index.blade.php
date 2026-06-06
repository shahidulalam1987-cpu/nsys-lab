@extends('layouts.admin')

@section('content')
    <h1>Salary Generate</h1>

    <a class="btn" href="/admin/payroll/create">Generate Salary</a>

    <p>Generate salary based on employee working days for the selected month.</p>

    <div class="card" style="margin-top:20px;">
        <form method="GET" action="/admin/payroll">
            <input type="month" name="month" value="{{ request('month') }}">

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
                @foreach(['unpaid' => 'Unpaid', 'partial' => 'Partially Paid', 'paid' => 'Paid'] as $value => $label)
                    <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <button class="btn" type="submit">Filter</button>
            <a href="/admin/payroll">Reset</a>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <p>Total Payable Salary (BDT)</p>
            <h2>BDT {{ number_format($summary['total_payable'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Total Paid Salary</p>
            <h2>BDT {{ number_format($summary['total_paid'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Total Remaining Due</p>
            <h2>BDT {{ number_format($summary['total_due'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Salary Records</p>
            <h2>{{ number_format($payrolls->count()) }}</h2>
        </div>
    </div>

    <div class="card">
        <table>
            <tr>
                <th>Salary Period</th>
                <th>Month</th>
                <th>Employee</th>
                <th>Client</th>
                <th>Working Days</th>
                <th>Non Working Days</th>
                <th>Payable Salary (BDT)</th>
                <th>Paid Salary</th>
                <th>Remaining Due</th>
                <th>Status</th>
                <th>Payment Date</th>
                <th>Action</th>
            </tr>
            @forelse($payrolls as $payroll)
                <tr>
                    <td>{{ $payroll->salary_period }}</td>
                    <td>{{ $payroll->salary_month?->format('Y-m') }}</td>
                    <td>{{ $payroll->employee?->name }}</td>
                    <td>{{ $payroll->client?->company_name ?: '-' }}</td>
                    <td>{{ $payroll->working_days ?? '-' }}</td>
                    <td>{{ $payroll->non_working_days ?? '-' }}</td>
                    <td>BDT {{ number_format($payroll->payable_salary, 2) }}</td>
                    <td>BDT {{ number_format($payroll->paid_amount, 2) }}</td>
                    <td>BDT {{ number_format(max($payroll->payable_salary - $payroll->paid_amount, 0), 2) }}</td>
                    <td>{{ ['unpaid' => 'Unpaid', 'partial' => 'Partially Paid', 'paid' => 'Paid'][$payroll->calculated_status] ?? ucfirst($payroll->calculated_status) }}</td>
                    <td>{{ $payroll->payment_date?->toDateString() ?: '-' }}</td>
                    <td>
                        <a href="/admin/payroll/{{ $payroll->id }}">View</a>
                        |
                        <a href="/admin/payroll/{{ $payroll->id }}/edit">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="12">No salary records found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
