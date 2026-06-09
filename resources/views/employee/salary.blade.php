@extends('layouts.employee')

@section('content')
    <h1>My Salary</h1>

    <div class="stats-grid">
        <div class="stat-card"><p>Total Generated Salary</p><h2>BDT {{ number_format($summary['total_generated'], 2) }}</h2></div>
        <div class="stat-card"><p>Total Paid Salary</p><h2>BDT {{ number_format($summary['total_paid'], 2) }}</h2></div>
        <div class="stat-card"><p>Current Due</p><h2>BDT {{ number_format($summary['current_due'], 2) }}</h2></div>
        <div class="stat-card"><p>Last Payment Date</p><h2>{{ $summary['last_payment_date']?->toDateString() ?: '-' }}</h2></div>
    </div>

    <div class="card">
        <h2>Salary History</h2>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Month</th>
                    <th>Salary Period</th>
                    <th>Client</th>
                    <th>Working Days</th>
                    <th>Generated Salary</th>
                    <th>Paid Salary</th>
                    <th>Due</th>
                    <th>Status</th>
                    <th>Payment Date</th>
                    <th>Slip</th>
                </tr>
                @forelse($payrolls as $payroll)
                    @php($due = max($payroll->payable_salary - $payroll->paid_amount, 0))
                    <tr>
                        <td>{{ $payroll->salary_month?->format('Y-m') ?: '-' }}</td>
                        <td>{{ $payroll->salary_period }}</td>
                        <td>{{ $payroll->client?->company_name ?: '-' }}</td>
                        <td>{{ $payroll->working_days ?? '-' }}</td>
                        <td>BDT {{ number_format($payroll->payable_salary, 2) }}</td>
                        <td>BDT {{ number_format($payroll->paid_amount, 2) }}</td>
                        <td>BDT {{ number_format($due, 2) }}</td>
                        <td>{{ ['upcoming' => 'Upcoming', 'unpaid' => 'Unpaid', 'partial' => 'Partially Paid', 'paid' => 'Paid'][$payroll->calculated_status] ?? ucfirst($payroll->calculated_status) }}</td>
                        <td>{{ $payroll->payment_date?->toDateString() ?: '-' }}</td>
                        <td><a class="btn" href="/employee/salary/{{ $payroll->id }}/slip">Download Salary Slip</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9">No salary history found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
