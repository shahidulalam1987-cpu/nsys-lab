@extends('layouts.admin')

@section('content')
    <h1>Generate Salary</h1>

    <a class="btn" href="/admin/payroll">Back to Salary Generate</a>

    <p>Generate salary based on employee working days for the selected month.</p>

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
        <h2>Select Employee and Month</h2>
        <form method="GET" action="/admin/payroll/create">
            <select name="employee_id" required>
                <option value="">Select Employee</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                        {{ $employee->name }} ({{ $employee->employee_id }})
                    </option>
                @endforeach
            </select>
            <input type="month" name="month" value="{{ request('month', $selectedMonth) }}" required>
            <button class="btn" type="submit">Calculate Salary</button>
        </form>
    </div>

    @if($selectedEmployee && $payable)
        <div class="card">
            <h2>Payable Salary (BDT)</h2>
            <p><strong>Employee:</strong> {{ $selectedEmployee->name }}</p>
            <p><strong>Month:</strong> {{ $payable['month']->format('Y-m') }}</p>
            <p><strong>Working Days:</strong> {{ $payable['counted_days'] }}</p>
            <p><strong>Non Working Days:</strong> {{ $payable['non_counted_days'] }}</p>
            <p><strong>Payable Salary (BDT):</strong> BDT {{ number_format($payable['payable_salary'], 2) }}</p>
        </div>

        <div class="card">
            <form method="POST" action="/admin/payroll">
                @csrf
                <input type="hidden" name="employee_id" value="{{ $selectedEmployee->id }}">
                <input type="hidden" name="salary_month" value="{{ $payable['month']->format('Y-m') }}">

                <p>
                    Paid Salary<br>
                    <input type="number" step="0.01" name="paid_amount" value="{{ old('paid_amount', 0) }}" required>
                </p>

                <p>
                    Payment Method<br>
                    <input type="text" name="payment_method" value="{{ old('payment_method') }}">
                </p>

                <p>
                    Payment Date<br>
                    <input type="date" name="payment_date" value="{{ old('payment_date') }}">
                </p>

                <p>
                    Note<br>
                    <textarea name="note">{{ old('note') }}</textarea>
                </p>

                <button class="btn" type="submit">Save Salary</button>
            </form>
        </div>
    @endif
@endsection
