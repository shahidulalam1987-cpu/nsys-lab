@extends('layouts.employee')

@section('content')
    <h1>My Salary</h1>

    <div class="stats-grid">
        <div class="stat-card"><p>Upcoming Salary</p><h2>{{ $employee->salaryStatusLabel() === 'Upcoming' ? 'Yes' : 'No' }}</h2></div>
        <div class="stat-card"><p>Next Salary Date</p><h2>{{ $employee->nextSalaryDate()?->toDateString() ?: '-' }}</h2></div>
        <div class="stat-card"><p>Total Paid</p><h2>BDT {{ number_format($payrolls->sum('paid_amount'), 2) }}</h2></div>
        <div class="stat-card"><p>Current Status</p><h2>{{ $employee->salaryStatusLabel() }}</h2></div>
    </div>

    <div class="card">
        <h2>Salary History</h2>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Salary Period</th>
                    <th>Client</th>
                    <th>Payable Salary</th>
                    <th>Paid Salary</th>
                    <th>Remaining Due</th>
                    <th>Status</th>
                </tr>
                @forelse($payrolls as $payroll)
                    <tr>
                        <td>{{ $payroll->salary_period }}</td>
                        <td>{{ $payroll->client?->company_name ?: '-' }}</td>
                        <td>BDT {{ number_format($payroll->payable_salary, 2) }}</td>
                        <td>BDT {{ number_format($payroll->paid_amount, 2) }}</td>
                        <td>BDT {{ number_format(max($payroll->payable_salary - $payroll->paid_amount, 0), 2) }}</td>
                        <td>{{ ['upcoming' => 'Upcoming', 'unpaid' => 'Unpaid', 'partial' => 'Partially Paid', 'paid' => 'Paid'][$payroll->calculated_status] ?? ucfirst($payroll->calculated_status) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No salary history found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
