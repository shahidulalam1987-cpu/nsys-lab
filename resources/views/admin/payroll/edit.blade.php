@extends('layouts.admin')

@section('content')
    <h1>Edit Salary</h1>

    <a class="btn" href="/admin/payroll/{{ $payroll->id }}">Back to Salary Details</a>

    @if ($errors->any())
        <div class="card" style="color:#ef4444; margin-top:20px;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="margin-top:20px;">
        <h2>{{ $payroll->employee?->name }} - {{ $payroll->salary_month?->format('Y-m') }}</h2>
        <p><strong>Salary Period:</strong> {{ $payroll->salary_period }}</p>
        <p><strong>Working Days:</strong> {{ $payroll->working_days ?? '-' }}</p>
        <p><strong>Non Working Days:</strong> {{ $payroll->non_working_days ?? '-' }}</p>
        <p><strong>Payable Salary (BDT):</strong> BDT {{ number_format($payroll->payable_salary, 2) }}</p>

        <form method="POST" action="/admin/payroll/{{ $payroll->id }}/update">
            @csrf

            <p>
                Paid Salary<br>
                <input type="number" step="0.01" name="paid_amount" value="{{ old('paid_amount', $payroll->paid_amount) }}" required>
            </p>

            <p>
                Payment Method<br>
                <input type="text" name="payment_method" value="{{ old('payment_method', $payroll->payment_method) }}">
            </p>

            <p>
                Payment Date<br>
                <input type="date" name="payment_date" value="{{ old('payment_date', $payroll->payment_date?->toDateString()) }}">
            </p>

            <p>
                Note<br>
                <textarea name="note">{{ old('note', $payroll->note) }}</textarea>
            </p>

            <button class="btn" type="submit">Update Salary</button>
        </form>
    </div>
@endsection
