@extends('layouts.admin')

@section('content')
    <h1>Salary Details</h1>

    <a class="btn" href="/admin/payroll">Back to Salary Generate</a>
    <a class="btn" href="/admin/payroll/{{ $payroll->id }}/edit">Edit Salary</a>

    <div class="card" style="margin-top:20px;">
        <h2>{{ $payroll->employee?->name }} - {{ $payroll->salary_month?->format('Y-m') }}</h2>
        <p><strong>Calculation Type:</strong> {{ $payroll->calculationTypeLabel() }}</p>
        <p><strong>Client:</strong> {{ $payroll->client?->company_name ?: '-' }}</p>
        <p><strong>Salary Period:</strong> {{ $payroll->salary_period }}</p>
        <p><strong>Working Days:</strong> {{ $payroll->working_days ?? '-' }}</p>
        <p><strong>Non Working Days:</strong> {{ $payroll->non_working_days ?? '-' }}</p>
        <p><strong>Monthly Salary:</strong> BDT {{ number_format($payroll->employee?->monthly_salary ?? 0, 2) }}</p>
        <p><strong>Month Days:</strong> {{ $payroll->month_days ?? '-' }}</p>
        <p><strong>Daily Salary:</strong> {{ $payroll->daily_salary !== null ? 'BDT ' . number_format($payroll->daily_salary, 2) : '-' }}</p>
        <p><strong>Payable Salary (BDT):</strong> BDT {{ number_format($payroll->payable_salary, 2) }}</p>
        <p><strong>Paid Salary:</strong> BDT {{ number_format($payroll->paid_amount, 2) }}</p>
        <p><strong>Remaining Due:</strong> BDT {{ number_format(max($payroll->payable_salary - $payroll->paid_amount, 0), 2) }}</p>
        <p><strong>Payment Status:</strong> {{ ['upcoming' => 'Upcoming', 'unpaid' => 'Unpaid', 'partial' => 'Partially Paid', 'paid' => 'Paid'][$payroll->calculated_status] ?? ucfirst($payroll->calculated_status) }}</p>
        <p><strong>Payment Method:</strong> {{ $payroll->payment_method ?: '-' }}</p>
        <p><strong>Payment Date:</strong> {{ $payroll->payment_date?->toDateString() ?: '-' }}</p>
        <p><strong>Transaction ID / Reference:</strong> {{ $payroll->transaction_id ?: '-' }}</p>
        <p><strong>Payment Proof:</strong>
            @if($payroll->payment_proof)
                <a href="/storage/{{ $payroll->payment_proof }}" target="_blank">View Proof</a>
            @else
                -
            @endif
        </p>
        <p><strong>Note:</strong> {{ $payroll->note ?: '-' }}</p>
    </div>
@endsection
