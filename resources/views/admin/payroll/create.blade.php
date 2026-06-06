@extends('layouts.admin')

@section('content')
    <h1>Generate Salary</h1>

    <a class="btn" href="/admin/payroll">Back to Salary Generate</a>

    <p>Generate salary directly by employee, client, date range, and working days. Daily salary day records are optional for audit only.</p>

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
        <h2>Salary Information</h2>

        <form method="POST" action="/admin/payroll">
            @csrf

            <p>Employee<br>
                <select name="employee_id" required>
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }} ({{ $employee->employee_id }}) - BDT {{ number_format($employee->monthly_salary, 2) }}
                        </option>
                    @endforeach
                </select>
            </p>

            <p>Client<br>
                <select name="client_id" required>
                    <option value="">Select Client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                            {{ $client->company_name }}
                        </option>
                    @endforeach
                </select>
            </p>

            <p>From Date<br><input type="date" name="from_date" value="{{ old('from_date', now()->startOfMonth()->toDateString()) }}" required></p>
            <p>To Date<br><input type="date" name="to_date" value="{{ old('to_date', now()->toDateString()) }}" required></p>
            <p>Working Days<br><input type="number" min="0" max="31" name="working_days" value="{{ old('working_days', 0) }}" required></p>
            <p>Non Working Days<br><input type="number" min="0" max="31" name="non_working_days" value="{{ old('non_working_days', 0) }}" required></p>
            <p>Paid Salary<br><input type="number" step="0.01" min="0" name="paid_amount" value="{{ old('paid_amount', 0) }}" required></p>
            <p>Payment Method<br><input type="text" name="payment_method" value="{{ old('payment_method') }}"></p>
            <p>Payment Date<br><input type="date" name="payment_date" value="{{ old('payment_date') }}"></p>
            <p>Note<br><textarea name="note">{{ old('note') }}</textarea></p>

            <p>Payable Salary will be calculated automatically from monthly salary / actual days in From Date month x working days.</p>

            <button class="btn" type="submit">Save Salary</button>
        </form>
    </div>
@endsection
