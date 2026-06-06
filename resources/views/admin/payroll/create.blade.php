@extends('layouts.admin')

@section('content')
    <h1>Generate Salary</h1>

    <a class="btn" href="/admin/payroll">Back to Salary Generate</a>

    <p>Generate salary by date range or monthly cycle from this page.</p>

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

        <form method="POST" action="/admin/payroll" id="salary-generate-form" enctype="multipart/form-data">
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

            <div id="date_adjustment_card" style="margin-top:20px;">
                <h2>Date-wise Adjustment</h2>
                <p>Mark dates as Non Working when needed. Salary will update automatically.</p>

                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day Type</th>
                            <th>Reason</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody id="date_adjustment_rows"></tbody>
                </table>
            </div>

            <p>Payment Status<br>
                <select name="payment_status" id="payment_status" required>
                    @foreach(['upcoming' => 'Upcoming', 'unpaid' => 'Unpaid', 'partial' => 'Partially Paid', 'paid' => 'Paid'] as $value => $label)
                        <option value="{{ $value }}" {{ old('payment_status', 'upcoming') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </p>
            <p>Paid Salary<br><input type="number" step="0.01" min="0" name="paid_amount" id="paid_amount" value="{{ old('paid_amount', 0) }}"></p>
            <p>Payment Method<br><input type="text" name="payment_method" id="payment_method" value="{{ old('payment_method') }}"></p>
            <p>Payment Date<br><input type="date" name="payment_date" id="payment_date" value="{{ old('payment_date') }}"></p>
            <p>Transaction ID / Reference<br><input type="text" name="transaction_id" value="{{ old('transaction_id') }}"></p>
            <p>Payment Proof Screenshot<br><input type="file" name="payment_proof" id="payment_proof" accept="image/*"></p>
            <p>Note<br><textarea name="note">{{ old('note') }}</textarea></p>

            <p>Date To Date is the default salary creation flow.</p>

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
        const nonWorkingDays = document.getElementById('non_working_days');
        const dateAdjustmentCard = document.getElementById('date_adjustment_card');
        const dateAdjustmentRows = document.getElementById('date_adjustment_rows');
        const paymentStatus = document.getElementById('payment_status');
        const paidAmount = document.getElementById('paid_amount');
        const paymentMethod = document.getElementById('payment_method');
        const paymentDate = document.getElementById('payment_date');
        const paymentProof = document.getElementById('payment_proof');
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

        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        }

        function inclusiveDaysBetween(startValue, endValue) {
            if (!startValue || !endValue) {
                return 0;
            }

            const start = new Date(startValue + 'T00:00:00');
            const end = new Date(endValue + 'T00:00:00');

            if (end < start) {
                return 0;
            }

            return Math.floor((end - start) / 86400000) + 1;
        }

        const reasonOptions = {
            active_working: 'Active Working',
            client_issue: 'Client Issue',
            boosting_off: 'Boosting OFF',
            business_closed: 'Business Closed',
            agency_hold: 'Agency Hold',
            on_leave: 'On Leave',
            sick_leave: 'Sick Leave',
            other: 'Other',
        };

        function dateRange(startValue, endValue) {
            if (!startValue || !endValue) {
                return [];
            }

            const start = new Date(startValue + 'T00:00:00');
            const end = new Date(endValue + 'T00:00:00');

            if (end < start) {
                return [];
            }

            const dates = [];
            const current = new Date(start);

            while (current <= end) {
                dates.push(formatDate(current));
                current.setDate(current.getDate() + 1);
            }

            return dates;
        }

        function syncMonthlyCycleDates() {
            if (calculationType.value !== 'monthly_cycle' || !salaryMonth.value) {
                return;
            }

            const start = salaryMonth.value + '-01';
            const parts = salaryMonth.value.split('-');
            const endDate = new Date(Number(parts[0]), Number(parts[1]), 0);
            fromDate.value = start;
            toDate.value = formatDate(endDate);
        }

        function syncWorkingDays() {
            if (calculationType.value === 'monthly_cycle') {
                workingDays.value = daysInMonth(fromDate.value);
                nonWorkingDays.value = Number(nonWorkingDays.value || 0);
                return;
            }

            const calculatedWorkingDays = dateAdjustmentRows.children.length > 0
                ? Array.from(dateAdjustmentRows.querySelectorAll('.day-type')).filter((field) => field.value === 'working').length
                : inclusiveDaysBetween(fromDate.value, toDate.value);
            const calculatedNonWorkingDays = dateAdjustmentRows.children.length > 0
                ? Array.from(dateAdjustmentRows.querySelectorAll('.day-type')).filter((field) => field.value === 'non_working').length
                : 0;

            if (calculatedWorkingDays > 0) {
                workingDays.value = calculatedWorkingDays;
            }

            nonWorkingDays.value = calculatedNonWorkingDays;
        }

        function renderDateAdjustments() {
            dateAdjustmentRows.innerHTML = '';

            if (calculationType.value !== 'date_to_date') {
                dateAdjustmentCard.style.display = 'none';
                return;
            }

            const dates = dateRange(fromDate.value, toDate.value);
            dateAdjustmentCard.style.display = dates.length > 0 ? 'block' : 'none';

            dates.forEach((date, index) => {
                const row = document.createElement('tr');
                const reasonSelectOptions = Object.entries(reasonOptions).map(([value, label]) => {
                    return `<option value="${value}" ${value === 'active_working' ? 'selected' : ''}>${label}</option>`;
                }).join('');

                row.innerHTML = `
                    <td>
                        ${date}
                        <input type="hidden" name="salary_day_adjustments[${index}][date]" value="${date}">
                    </td>
                    <td>
                        <select name="salary_day_adjustments[${index}][day_type]" class="day-type">
                            <option value="working" selected>Working</option>
                            <option value="non_working">Non Working</option>
                        </select>
                    </td>
                    <td>
                        <select name="salary_day_adjustments[${index}][reason]" class="day-reason">
                            ${reasonSelectOptions}
                        </select>
                    </td>
                    <td>
                        <input type="text" name="salary_day_adjustments[${index}][note]" placeholder="Optional note">
                    </td>
                `;

                dateAdjustmentRows.appendChild(row);
            });

            dateAdjustmentRows.querySelectorAll('.day-type').forEach((field) => {
                field.addEventListener('change', () => {
                    const reason = field.closest('tr').querySelector('.day-reason');

                    if (field.value === 'working') {
                        reason.value = 'active_working';
                    }

                    syncWorkingDays();
                    calculateSalary();
                });
            });
        }

        function calculateSalary() {
            const selected = employeeSelect.options[employeeSelect.selectedIndex];
            const monthlySalary = Number(selected?.dataset.salary || 0);
            const monthDays = daysInMonth(fromDate.value);
            const dailySalary = monthDays > 0 ? monthlySalary / monthDays : 0;
            const payableSalary = monthDays > 0 ? (monthlySalary * Number(workingDays.value || 0)) / monthDays : 0;
            const due = Math.max(payableSalary - Number(paidAmount.value || 0), 0);
            const needsPaymentDetails = ['partial', 'paid'].includes(paymentStatus.value);

            monthlySalaryDisplay.value = money(monthlySalary);
            monthDaysDisplay.value = monthDays;
            dailySalaryDisplay.value = money(dailySalary);
            payableSalaryDisplay.value = money(payableSalary);
            dueDisplay.value = money(due);
            paidAmount.required = needsPaymentDetails;
            paymentMethod.required = needsPaymentDetails;
            paymentDate.required = needsPaymentDetails;
            paymentProof.required = false;
        }

        function syncDatesAndSalary() {
            syncMonthlyCycleDates();
            renderDateAdjustments();
            syncWorkingDays();
            calculateSalary();
        }

        [calculationType, salaryMonth, fromDate, toDate].forEach((field) => {
            field.addEventListener('input', syncDatesAndSalary);
            field.addEventListener('change', syncDatesAndSalary);
        });

        [employeeSelect, workingDays, paymentStatus, paidAmount].forEach((field) => {
            field.addEventListener('input', calculateSalary);
            field.addEventListener('change', calculateSalary);
        });

        @if(old('working_days') === null)
            syncDatesAndSalary();
        @else
            calculateSalary();
        @endif
    </script>
@endsection
