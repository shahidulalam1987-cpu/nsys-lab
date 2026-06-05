@extends('layouts.admin')

@section('content')
    <h1>Create Payroll</h1>

    <a class="btn" href="/admin/payroll">Back to Payroll</a>

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
            <button class="btn" type="submit">Calculate Payable</button>
        </form>
    </div>

    @if($selectedEmployee && $payable)
        <div class="card">
            <h2>Payable Salary</h2>
            <p><strong>Employee:</strong> {{ $selectedEmployee->name }}</p>
            <p><strong>Month:</strong> {{ $payable['month']->format('Y-m') }}</p>
            <p><strong>Counted Days:</strong> {{ $payable['counted_days'] }}</p>
            <p><strong>Non-Counted Days:</strong> {{ $payable['non_counted_days'] }}</p>
            <p><strong>Payable Salary:</strong> BDT {{ number_format($payable['payable_salary'], 2) }}</p>
        </div>

        <div class="card">
            <form method="POST" action="/admin/payroll">
                @csrf
                <input type="hidden" name="employee_id" value="{{ $selectedEmployee->id }}">
                <input type="hidden" name="salary_month" value="{{ $payable['month']->format('Y-m') }}">

                <p>
                    Paid Amount<br>
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

                <button class="btn" type="submit">Save Payroll</button>
            </form>
        </div>
    @endif
@endsection
