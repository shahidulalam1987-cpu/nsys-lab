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

    <style>
        .salary-form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px 16px;
            align-items: end;
        }

        .salary-setup-grid {
            grid-template-columns: minmax(320px, 2fr) minmax(220px, 1fr) minmax(180px, 1fr);
        }

        .calculation-grid {
            grid-template-columns: repeat(4, minmax(160px, 1fr));
        }

        .payment-grid {
            grid-template-columns: repeat(5, minmax(150px, 1fr));
        }

        .salary-section {
            border: 1px solid #243044;
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
            background: rgba(15, 23, 42, 0.35);
        }

        .salary-section h2 {
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 18px;
        }

        .salary-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin: 0;
            min-width: 0;
        }

        .salary-field.full-width {
            grid-column: 1 / -1;
        }

        .salary-field.proof-field {
            grid-column: span 2;
        }

        .salary-field.note-field {
            grid-column: span 3;
        }

        .salary-field input,
        .salary-field select,
        .salary-field textarea {
            width: 100%;
            min-width: 0;
            min-height: 42px;
            box-sizing: border-box;
        }

        .salary-field textarea {
            min-height: 42px;
            resize: vertical;
        }

        .salary-field input[readonly] {
            color: #dbeafe;
            background: #111827;
            border-color: #334155;
        }

        .fund-warning {
            display: none;
            margin-top: 12px;
            padding: 12px;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            color: #fcd34d;
            background: rgba(245, 158, 11, .12);
        }

        .salary-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 18px;
        }

        .date-adjustment-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }

        .date-adjustment-header h2 {
            margin-bottom: 0;
        }

        #date_adjustment_body {
            margin-top: 12px;
            overflow-x: auto;
        }

        #date_adjustment_body table {
            min-width: 760px;
        }

        #date_adjustment_body th,
        #date_adjustment_body td {
            vertical-align: middle;
            white-space: nowrap;
        }

        #date_adjustment_body td:nth-child(4) {
            min-width: 220px;
            white-space: normal;
        }

        #date_adjustment_body input,
        #date_adjustment_body select {
            width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }

        @media (max-width: 1180px) {
            .salary-setup-grid,
            .calculation-grid,
            .payment-grid {
                grid-template-columns: repeat(2, minmax(220px, 1fr));
            }

            .salary-field.proof-field,
            .salary-field.note-field {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 720px) {
            .salary-form-grid,
            .salary-setup-grid,
            .calculation-grid,
            .payment-grid {
                grid-template-columns: 1fr;
            }

            .date-adjustment-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .salary-actions {
                justify-content: stretch;
            }

            .salary-actions .btn {
                width: 100%;
            }
        }
    </style>

    <div class="card" style="margin-top:20px;">
        <h2>Salary Information</h2>

        <form method="POST" action="/admin/payroll" id="salary-generate-form" enctype="multipart/form-data">
            @csrf

            <div class="salary-section">
                <h2>Salary Setup</h2>
                <div class="salary-form-grid salary-setup-grid">
                    <p class="salary-field">Employee<br>
                        <select name="employee_id" id="employee_id" required>
                            <option value="" data-salary="0">Select Employee</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" data-salary="{{ (float) $employee->monthly_salary }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->name }} ({{ $employee->employee_id }}) - BDT {{ number_format($employee->monthly_salary, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </p>

                    <p class="salary-field">Client<br>
                        <select name="client_id" id="client_id" required>
                            <option value="">Select Client</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" data-balance="{{ $clientBalances[$client->id] ?? 0 }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                    {{ $client->company_name }}
                                </option>
                            @endforeach
                        </select>
                    </p>

                    <p class="salary-field">Calculation Type<br>
                        <select name="calculation_type" id="calculation_type" required>
                            <option value="date_to_date" {{ old('calculation_type', 'date_to_date') === 'date_to_date' ? 'selected' : '' }}>Date To Date</option>
                            <option value="monthly_cycle" {{ old('calculation_type') === 'monthly_cycle' ? 'selected' : '' }}>Monthly Cycle</option>
                        </select>
                    </p>

                    <p class="salary-field">Salary Month<br><input type="month" name="salary_month" id="salary_month" value="{{ old('salary_month', now()->format('Y-m')) }}"></p>
                    <p class="salary-field">From Date<br><input type="date" name="from_date" id="from_date" value="{{ old('from_date', now()->startOfMonth()->toDateString()) }}"></p>
                    <p class="salary-field">To Date<br><input type="date" name="to_date" id="to_date" value="{{ old('to_date', now()->toDateString()) }}"></p>
                    <p class="salary-field">Use Work Status Records<br>
                        <label style="display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" name="use_work_status_records" id="use_work_status_records" value="1" {{ old('use_work_status_records') ? 'checked' : '' }}>
                            Auto-fill working days
                        </label>
                    </p>
                </div>
                <div class="fund-warning" id="fund_warning"></div>
            </div>

            <div class="salary-section">
                <h2>Calculation Summary</h2>
                <div class="salary-form-grid calculation-grid">
                    <p class="salary-field">Working Days<br><input type="number" step="0.5" min="0" max="31" name="working_days" id="working_days" value="{{ old('working_days') }}"></p>
                    <p class="salary-field">Non Working Days<br><input type="number" step="0.5" min="0" max="31" name="non_working_days" id="non_working_days" value="{{ old('non_working_days', 0) }}"></p>
                    <p class="salary-field">Monthly Salary<br><input type="text" id="monthly_salary_display" value="BDT 0.00" readonly></p>
                    <p class="salary-field">Month Days<br><input type="text" id="month_days_display" value="30" readonly></p>
                    <p class="salary-field">Daily Salary<br><input type="text" id="daily_salary_display" value="BDT 0.00" readonly></p>
                    <p class="salary-field">Payable Salary (BDT)<br><input type="text" id="payable_salary_display" value="BDT 0.00" readonly></p>
                    <p class="salary-field">Due<br><input type="text" id="due_display" value="BDT 0.00" readonly></p>
                </div>
            </div>

            <div class="salary-section" id="date_adjustment_card">
                <div class="date-adjustment-header">
                    <h2>Date-wise Adjustment</h2>
                    <button class="btn" type="button" id="date_adjustment_toggle">Show Date Adjustments</button>
                </div>
                <p>Mark dates as Non Working when needed. Salary will update automatically.</p>

                <div id="date_adjustment_body">
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
            </div>

            <div class="salary-section">
                <h2>Payment Information</h2>
                <p>Salary status is calculated automatically from salary date and paid salary.</p>
                <div class="salary-form-grid payment-grid">
                    <p class="salary-field">Paid Salary<br><input type="number" step="0.01" min="0" name="paid_amount" id="paid_amount" value="{{ old('paid_amount', 0) }}"></p>
                    <p class="salary-field">Payment Method<br><input type="text" name="payment_method" id="payment_method" value="{{ old('payment_method') }}"></p>
                    <p class="salary-field">Payment Date<br><input type="date" name="payment_date" id="payment_date" value="{{ old('payment_date') }}"></p>
                    <p class="salary-field">Transaction ID / Reference<br><input type="text" name="transaction_id" value="{{ old('transaction_id') }}"></p>
                    <p class="salary-field proof-field">Payment Proof<br><input type="file" name="payment_proof" id="payment_proof" accept="image/*"></p>
                    <p class="salary-field note-field">Note<br><textarea name="note">{{ old('note') }}</textarea></p>
                </div>
            </div>

            <div class="salary-actions">
                <button class="btn" type="submit">Save Salary</button>
            </div>
        </form>
    </div>

    <script>
        const employeeSelect = document.getElementById('employee_id');
        const clientSelect = document.getElementById('client_id');
        const calculationType = document.getElementById('calculation_type');
        const salaryMonth = document.getElementById('salary_month');
        const fromDate = document.getElementById('from_date');
        const toDate = document.getElementById('to_date');
        const useWorkStatusRecords = document.getElementById('use_work_status_records');
        const workingDays = document.getElementById('working_days');
        const nonWorkingDays = document.getElementById('non_working_days');
        const dateAdjustmentCard = document.getElementById('date_adjustment_card');
        const dateAdjustmentBody = document.getElementById('date_adjustment_body');
        const dateAdjustmentToggle = document.getElementById('date_adjustment_toggle');
        const dateAdjustmentRows = document.getElementById('date_adjustment_rows');
        const paidAmount = document.getElementById('paid_amount');
        const paymentMethod = document.getElementById('payment_method');
        const paymentDate = document.getElementById('payment_date');
        const paymentProof = document.getElementById('payment_proof');
        const monthlySalaryDisplay = document.getElementById('monthly_salary_display');
        const monthDaysDisplay = document.getElementById('month_days_display');
        const dailySalaryDisplay = document.getElementById('daily_salary_display');
        const payableSalaryDisplay = document.getElementById('payable_salary_display');
        const dueDisplay = document.getElementById('due_display');
        const fundWarning = document.getElementById('fund_warning');
        const fixedSalaryMonthDays = 30;
        let dateAdjustmentsExpanded = true;
        const workStatusRecords = @json($workStatusRecords ?? []);
        const workStatusReasonMap = {
            working: 'active_working',
            half_day: 'active_working',
            absent: 'absent',
            on_leave: 'on_leave',
            client_issue: 'client_issue',
            boosting_off: 'boosting_off',
            sick_leave: 'sick_leave',
            agency_closed: 'agency_closed',
        };

        function money(amount) {
            return 'BDT ' + amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function fixedMonthDays() {
            return fixedSalaryMonthDays;
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
            absent: 'Absent',
            client_issue: 'Client Issue',
            boosting_off: 'Boosting OFF',
            business_closed: 'Business Closed',
            agency_hold: 'Agency Hold',
            on_leave: 'On Leave',
            sick_leave: 'Sick Leave',
            holiday: 'Holiday',
            agency_closed: 'Agency Closed',
            other: 'Other',
        };

        function workStatusForDate(date) {
            if (!useWorkStatusRecords.checked || !employeeSelect.value) {
                return null;
            }

            return workStatusRecords.find((workStatus) => {
                const employeeMatches = String(workStatus.employee_id) === String(employeeSelect.value);
                const clientMatches = !clientSelect.value || !workStatus.client_id || String(workStatus.client_id) === String(clientSelect.value);

                return employeeMatches && clientMatches && workStatus.date === date;
            }) || null;
        }

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

        function setDateAdjustmentExpanded(expanded) {
            dateAdjustmentsExpanded = expanded;
            dateAdjustmentBody.style.display = expanded ? 'block' : 'none';
            dateAdjustmentToggle.textContent = expanded ? 'Hide Date Adjustments' : 'Show Date Adjustments';
        }

        function syncWorkingDays() {
            if (calculationType.value === 'monthly_cycle') {
                workingDays.value = fixedMonthDays();
                nonWorkingDays.value = Number(nonWorkingDays.value || 0);
                return;
            }

            const calculatedWorkingDays = dateAdjustmentRows.children.length > 0
                ? Array.from(dateAdjustmentRows.querySelectorAll('.salary-count-value')).reduce((total, field) => total + Number(field.value || 0), 0)
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
            setDateAdjustmentExpanded(dates.length <= 5);

            dates.forEach((date, index) => {
                const workStatus = workStatusForDate(date);
                const salaryCount = workStatus
                    ? Number(workStatus.salary_count_value || 0)
                    : (useWorkStatusRecords.checked ? 0 : 1);
                const dayType = salaryCount > 0 ? 'working' : 'non_working';
                const selectedReason = workStatus
                    ? (workStatusReasonMap[workStatus.status] || 'other')
                    : (useWorkStatusRecords.checked ? 'other' : 'active_working');
                const note = workStatus
                    ? (workStatus.note || workStatus.status_label || 'From work status record')
                    : (useWorkStatusRecords.checked ? 'No work status record' : '');
                const row = document.createElement('tr');
                const reasonSelectOptions = Object.entries(reasonOptions).map(([value, label]) => {
                    return `<option value="${value}" ${value === selectedReason ? 'selected' : ''}>${label}</option>`;
                }).join('');

                row.innerHTML = `
                    <td>
                        ${date}
                        <input type="hidden" name="salary_day_adjustments[${index}][date]" value="${date}">
                        <input type="hidden" name="salary_day_adjustments[${index}][salary_count_value]" class="salary-count-value" value="${salaryCount}">
                    </td>
                    <td>
                        <select name="salary_day_adjustments[${index}][day_type]" class="day-type">
                            <option value="working" ${dayType === 'working' ? 'selected' : ''}>Working</option>
                            <option value="non_working" ${dayType === 'non_working' ? 'selected' : ''}>Non Working</option>
                        </select>
                    </td>
                    <td>
                        <select name="salary_day_adjustments[${index}][reason]" class="day-reason">
                            ${reasonSelectOptions}
                        </select>
                    </td>
                    <td>
                        <input type="text" name="salary_day_adjustments[${index}][note]" value="${note.replace(/"/g, '&quot;')}" placeholder="Optional note">
                    </td>
                `;

                dateAdjustmentRows.appendChild(row);
            });

            dateAdjustmentRows.querySelectorAll('.day-type').forEach((field) => {
                field.addEventListener('change', () => {
                    const row = field.closest('tr');
                    const reason = row.querySelector('.day-reason');
                    const salaryCountField = row.querySelector('.salary-count-value');

                    if (field.value === 'working') {
                        reason.value = 'active_working';
                        salaryCountField.value = salaryCountField.value === '0' ? 1 : salaryCountField.value;
                    } else {
                        salaryCountField.value = 0;
                    }

                    syncWorkingDays();
                    calculateSalary();
                });
            });
        }

        function calculateSalary() {
            const selected = employeeSelect.options[employeeSelect.selectedIndex];
            const selectedClient = clientSelect.options[clientSelect.selectedIndex];
            const monthlySalary = Number(selected?.dataset.salary || 0);
            const clientBalance = Number(selectedClient?.dataset.balance || 0);
            const monthDays = fixedMonthDays();
            const dailySalary = monthDays > 0 ? Math.round((monthlySalary / monthDays) * 100) / 100 : 0;
            const payableSalary = Number(workingDays.value || 0) >= fixedSalaryMonthDays
                ? monthlySalary
                : (monthDays > 0 ? dailySalary * Number(workingDays.value || 0) : 0);
            const due = Math.max(payableSalary - Number(paidAmount.value || 0), 0);
            const needsPaymentDetails = Number(paidAmount.value || 0) > 0;

            monthlySalaryDisplay.value = money(monthlySalary);
            monthDaysDisplay.value = monthDays;
            dailySalaryDisplay.value = money(dailySalary);
            payableSalaryDisplay.value = money(payableSalary);
            dueDisplay.value = money(due);
            if (clientSelect.value && payableSalary > clientBalance) {
                fundWarning.style.display = 'block';
                fundWarning.innerHTML = `<strong>Insufficient Client Fund</strong><br>Need: ${money(payableSalary)}<br>Available: ${money(clientBalance)}<br>Admin can still save salary if approved.`;
            } else {
                fundWarning.style.display = 'none';
                fundWarning.innerHTML = '';
            }
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

        dateAdjustmentToggle.addEventListener('click', () => {
            setDateAdjustmentExpanded(!dateAdjustmentsExpanded);
        });

        [calculationType, salaryMonth, fromDate, toDate, employeeSelect, clientSelect, useWorkStatusRecords].forEach((field) => {
            field.addEventListener('input', syncDatesAndSalary);
            field.addEventListener('change', syncDatesAndSalary);
        });

        [workingDays, paidAmount].forEach((field) => {
            field.addEventListener('input', calculateSalary);
            field.addEventListener('change', calculateSalary);
        });

        syncMonthlyCycleDates();
        renderDateAdjustments();
        @if(old('working_days') === null)
            syncWorkingDays();
        @endif
        calculateSalary();
    </script>
@endsection
