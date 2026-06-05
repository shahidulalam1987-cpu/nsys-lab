@extends('layouts.admin')

@section('content')
    <h1>Payroll Details</h1>

    <a class="btn" href="/admin/payroll">Back to Payroll</a>
    <a class="btn" href="/admin/payroll/{{ $payroll->id }}/edit">Edit Payroll</a>

    <div class="card" style="margin-top:20px;">
        <h2>{{ $payroll->employee?->name }} - {{ $payroll->salary_month?->format('Y-m') }}</h2>
        <p><strong>Client:</strong> {{ $payroll->client?->company_name ?: '-' }}</p>
        <p><strong>Payable Salary:</strong> BDT {{ number_format($payroll->payable_salary, 2) }}</p>
        <p><strong>Paid Amount:</strong> BDT {{ number_format($payroll->paid_amount, 2) }}</p>
        <p><strong>Due:</strong> BDT {{ number_format(max($payroll->payable_salary - $payroll->paid_amount, 0), 2) }}</p>
        <p><strong>Status:</strong> {{ ucfirst($payroll->calculated_status) }}</p>
        <p><strong>Payment Method:</strong> {{ $payroll->payment_method ?: '-' }}</p>
        <p><strong>Payment Date:</strong> {{ $payroll->payment_date?->toDateString() ?: '-' }}</p>
        <p><strong>Note:</strong> {{ $payroll->note ?: '-' }}</p>
    </div>
@endsection
