@extends('layouts.admin')

@section('content')
    <h1>Generate Salary</h1>

    <a class="btn" href="/admin/payroll">Back to Salary Generate</a>

    <p>Generate salary by date range or monthly cycle. Salary Day records are optional audit records only.</p>

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

        <form method="POST" action="/admin/payroll" id="salary-generate-form">
            @csrf

            <p>Employee<br>
                <select name="employee_id" id="employee_id" required>
                    <option value="" data-salary="0">Select Employee</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" data-salary="{{ (float) $employee->monthly_salary }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
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

            <p>Calculation Type<br>
                <select name="calculation_type" id="calculation_type" required>
                    <option value="date_to_date" {{ old('calculation_type', 'date_to_date') === 'date_to_date' ? 'selected' : '' }}>Date To Date</option>
                    <option value="monthly_cycle" {{ old('calculation_type') === 'monthly_cycle' ? 'selected' : '' }}>Monthly Cycle</option>
                </select>
            </p>

            <p>Salary Month<br><input type="month" name="salary_month" id="salary_month" value="{{ old('salary_month', now()->format('Y-m')) }}"></p>
            <p>From Date<br><input type="date" name="from_date" id="from_date" value="{{ old('from_date', now()->startOfMonth()->toDateString()) }}"></p>
            <p>To Date<br><input type="date" name="to_date" id="to_date" value="{{ old('to_date', now()->toDateString()) }}"></p>
            <p>Working Days<br><input type="number" min="0" max="31" name="working_days" id="working_days" value="{{ old('working_days') }}" placeholder="Required for Date To Date"></p>
            <p>Non Working Days<br><input type="number" min="0" max="31" name="non_working_days" id="non_working_days" value="{{ old('non_working_days', 0) }}"></p>

            <p>Monthly Salary<br><input type="text" id="monthly_salary_display" value="BDT 0.00" readonly></p>
            <p>Month Days<br><input type="text" id="month_days_display" value="0" readonly></p>
            <p>Daily Salary<br><input type="text" id="daily_salary_display" value="BDT 0.00" readonly></p>
            <p>Payable Salary (BDT)<br><input type="text" id="payable_salary_display" value="BDT 0.00" readonly></p>
            <p>Due<br><input type="text" id="due_display" value="BDT 0.00" readonly></p>

            <p>Paid Salary<br><input type="number" step="0.01" min="0" name="paid_amount" id="paid_amount" value="{{ old('paid_amount', 0) }}" required></p>
            <p>Payment Method<br><input type="text" name="payment_method" value="{{ old('payment_method') }}"></p>
            <p>Payment Date<br><input type="date" name="payment_date" value="{{ old('payment_date') }}"></p>
            <p>Note<br><textarea name="note">{{ old('note') }}</textarea></p>

            <p>Date To Date is the default. Monthly Cycle can use optional Salary Day audit records if Working Days is left blank.</p>

            <button class="btn" type="submit">Save Salary</button>
        </form>
    </div>

    <script>
        const employeeSelect = document.getElementById('employee_id');
        const calculationType = document.getElementById('calculation_type');
        const salaryMonth = document.getElementById('salary_month');
        const fromDate = document.getElementById('from_date');
        const toDate = document.getElementById('to_date');
        const workingDays = document.getElementById('working_days');
        const paidAmount = document.getElementById('paid_amount');
        const monthlySalaryDisplay = document.getElementById('monthly_salary_display');
        const monthDaysDisplay = document.getElementById('month_days_display');
        const dailySalaryDisplay = document.getElementById('daily_salary_display');
        const payableSalaryDisplay = document.getElementById('payable_salary_display');
        const dueDisplay = document.getElementById('due_display');

        function money(amount) {
            return 'BDT ' + amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function daysInMonth(dateValue) {
            const date = dateValue ? new Date(dateValue + 'T00:00:00') : new Date();
            return new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
        }

        function syncMonthlyCycleDates() {
            if (calculationType.value !== 'monthly_cycle' || !salaryMonth.value) {
                return;
            }

            const start = salaryMonth.value + '-01';
            const parts = salaryMonth.value.split('-');
            const endDate = new Date(Number(parts[0]), Number(parts[1]), 0);
            fromDate.value = start;
            toDate.value = endDate.toISOString().slice(0, 10);
        }

        function calculateSalary() {
            syncMonthlyCycleDates();

            const selected = employeeSelect.options[employeeSelect.selectedIndex];
            const monthlySalary = Number(selected?.dataset.salary || 0);
            const monthDays = daysInMonth(fromDate.value);
            const dailySalary = monthDays > 0 ? monthlySalary / monthDays : 0;
            const payableSalary = dailySalary * Number(workingDays.value || 0);
            const due = Math.max(payableSalary - Number(paidAmount.value || 0), 0);

            monthlySalaryDisplay.value = money(monthlySalary);
            monthDaysDisplay.value = monthDays;
            dailySalaryDisplay.value = money(dailySalary);
            payableSalaryDisplay.value = money(payableSalary);
            dueDisplay.value = money(due);
        }

        [employeeSelect, calculationType, salaryMonth, fromDate, toDate, workingDays, paidAmount].forEach((field) => {
            field.addEventListener('input', calculateSalary);
            field.addEventListener('change', calculateSalary);
        });

        calculateSalary();
    </script>
@endsection
