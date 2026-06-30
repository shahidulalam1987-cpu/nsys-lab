@extends('layouts.admin')

@section('content')
    <h1>Generate Salary</h1>

    <a class="btn" href="/admin/payroll?status=due">Back to Unpaid Salary</a>

    <p>Generate salary directly from Work Status records, or use manual date-to-date entry for special cases.</p>

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

        .employee-payment-card {
            display: none;
            border: 1px solid rgba(66, 232, 255, .26);
            border-radius: 10px;
            padding: 14px;
            margin-top: 14px;
            background: rgba(47, 140, 255, .08);
        }

        .employee-payment-card.is-visible {
            display: block;
        }

        .employee-payment-header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .employee-payment-header h3 {
            margin: 0;
        }

        .payment-warning-badge {
            display: none;
            border-radius: 999px;
            color: #fcd34d;
            background: rgba(245, 158, 11, .16);
            border: 1px solid rgba(245, 158, 11, .35);
            font-size: 12px;
            font-weight: 800;
            padding: 7px 10px;
        }

        .payment-warning-badge.is-visible {
            display: inline-block;
        }

        .employee-payment-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }

        .employee-payment-item {
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 9px;
            padding: 10px;
            background: rgba(255,255,255,.05);
        }

        .employee-payment-item span {
            display: block;
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .04em;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .employee-payment-item strong {
            overflow-wrap: anywhere;
        }

        .employee-payment-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 12px;
        }

        .employee-payment-actions button:disabled {
            cursor: not-allowed;
            opacity: .48;
        }

        .salary-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 18px;
        }

        .mode-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .mode-tab {
            border: 1px solid var(--line);
            border-radius: 10px;
            color: var(--muted);
            cursor: pointer;
            font-weight: 700;
            padding: 10px 14px;
            background: rgba(255,255,255,.06);
        }

        .mode-tab.active-mode {
            color: #fff;
            background: linear-gradient(90deg, var(--blue), var(--cyan));
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

    @php
        $quickSalaryContext = $quickSalaryContext ?? null;
        $duplicateCyclePayroll = $duplicateCyclePayroll ?? null;
        $activeMode = old('generation_mode', 'work_status');
        $quickEmployeeId = data_get($quickSalaryContext, 'employee.id');
        $quickClientId = data_get($quickSalaryContext, 'client_id');
        $quickCycleStart = data_get($quickSalaryContext, 'cycle_start')?->toDateString();
        $quickCycleEnd = data_get($quickSalaryContext, 'cycle_end')?->toDateString();
        $quickSalaryDate = data_get($quickSalaryContext, 'salary_date')?->toDateString();
        $quickSalaryMonth = $quickCycleStart ? \Carbon\Carbon::parse($quickCycleStart)->format('Y-m') : ($quickSalaryDate ? \Carbon\Carbon::parse($quickSalaryDate)->format('Y-m') : now()->format('Y-m'));
    @endphp

    <div class="mode-tabs">
        <button class="mode-tab {{ $activeMode === 'work_status' ? 'active-mode' : '' }}" type="button" data-mode-target="work_status">Generate From Work Status</button>
        <button class="mode-tab {{ $activeMode === 'manual' ? 'active-mode' : '' }}" type="button" data-mode-target="manual">Manual Date-to-Date</button>
    </div>

    @if($quickSalaryContext)
        @php($assignment = data_get($quickSalaryContext, 'assignment'))
        @php($lastPayroll = data_get($quickSalaryContext, 'last_payroll'))
        <div class="card" style="margin-top:16px;">
            <h2 style="margin-top:0;">Salary Cycle Setup</h2>
            <div class="salary-form-grid calculation-grid">
                <p><strong>Employee</strong><br>{{ data_get($quickSalaryContext, 'employee.name') }} ({{ data_get($quickSalaryContext, 'employee.employee_id') }})</p>
                <p><strong>Client</strong><br>{{ $assignment?->client?->company_name ?: 'No Client / Agency Payroll' }}</p>
                <p><strong>Page</strong><br>{{ $assignment?->page?->page_name ?: '-' }}</p>
                <p><strong>Campaign</strong><br>{{ $assignment?->campaignRecord?->campaign_name ?: ($assignment?->campaign ?: '-') }}</p>
                <p><strong>Shift</strong><br>{{ $assignment?->shift?->name ?: data_get($quickSalaryContext, 'employee.shift.name', '-') }}</p>
                <p><strong>Salary Source</strong><br>{{ data_get($quickSalaryContext, 'employee')->salarySourceLabel() }}</p>
                <p><strong>Cycle</strong><br>{{ $quickCycleStart ?: '-' }} to {{ $quickCycleEnd ?: '-' }}</p>
                <p><strong>Salary Date</strong><br>{{ $quickSalaryDate ?: '-' }}</p>
            </div>

            @if($lastPayroll)
                <div style="margin-top:12px;color:var(--muted);">
                    <strong style="color:var(--text);">Last Payroll:</strong>
                    #{{ $lastPayroll->id }} · {{ $lastPayroll->salary_period }} · {{ $lastPayroll->reportStatusLabel() }} ·
                    {{ (float)$lastPayroll->paid_amount >= (float)$lastPayroll->payable_salary ? 'Paid' : 'Unpaid' }}
                </div>
            @endif
        </div>
    @endif

    @if($duplicateCyclePayroll)
        <div class="card" style="margin-top:16px;border-color:#f59e0b;color:#fcd34d;">
            <strong>Salary already generated for this cycle.</strong>
            <a class="btn" href="/admin/payroll/{{ $duplicateCyclePayroll->id }}" style="margin-left:10px;">View Existing Salary</a>
        </div>
    @endif

    <div class="card salary-mode-panel" id="work-status-mode-card" style="margin-top:20px;">
        <h2>Generate From Work Status</h2>
        <p>Use this mode when daily Work Status records are already added. Salary will be grouped by employee and client.</p>

        <form method="POST" action="/admin/payroll">
            @csrf
            <input type="hidden" name="generation_mode" value="work_status">
            <input type="hidden" name="work_status_action" value="preview">
            <input type="hidden" name="salary_date" value="{{ $quickSalaryDate }}">
            <input type="hidden" name="cycle_start" value="{{ $quickCycleStart }}">
            <input type="hidden" name="cycle_end" value="{{ $quickCycleEnd }}">
            @if($quickSalaryContext)<input type="hidden" name="return_to" value="/admin/payroll?status=due">@endif

            <div class="salary-section">
                <h2>Preview Setup</h2>
                <div class="salary-form-grid salary-setup-grid">
                    <p class="salary-field">Month<br>
                        <input type="month" name="salary_month" value="{{ old('salary_month', $workStatusFilters['salary_month'] ?? now()->format('Y-m')) }}" required>
                    </p>
                    <p class="salary-field">Employee<br>
                        <select name="employee_id" class="employee-payment-select">
                            <option value="">All Employees</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ (string) old('employee_id', $workStatusFilters['employee_id'] ?? '') === (string) $employee->id ? 'selected' : '' }}>
                                    {{ $employee->name }} ({{ $employee->employee_id }})
                                </option>
                            @endforeach
                        </select>
                    </p>
                    <p class="salary-field">Client<br>
                        <select name="client_id">
                            <option value="">{{ data_get($quickSalaryContext, 'employee')?->isAgencyInternal() ? 'No Client / Agency Payroll' : 'All Clients' }}</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ (string) old('client_id', $workStatusFilters['client_id'] ?? '') === (string) $client->id ? 'selected' : '' }}>
                                    {{ $client->company_name }}
                                </option>
                            @endforeach
                        </select>
                    </p>
                </div>
                @include('admin.payroll.partials.employee-payment-card')
                <div class="salary-actions">
                    <button class="btn" type="submit">Preview Salary</button>
                </div>
            </div>
        </form>

        @if($workStatusPreviewRows !== null)
            <form method="POST" action="/admin/payroll">
                @csrf
                <input type="hidden" name="generation_mode" value="work_status">
                <input type="hidden" name="work_status_action" value="generate">
                <input type="hidden" name="salary_month" value="{{ $workStatusFilters['salary_month'] }}">
                <input type="hidden" name="salary_date" value="{{ $workStatusFilters['salary_date'] ?? '' }}">
                <input type="hidden" name="cycle_start" value="{{ $workStatusFilters['cycle_start'] ?? '' }}">
                <input type="hidden" name="cycle_end" value="{{ $workStatusFilters['cycle_end'] ?? '' }}">
                @if($quickSalaryContext || data_get($workStatusFilters, 'return_to'))<input type="hidden" name="return_to" value="/admin/payroll?status=due">@endif

                <div class="salary-section">
                    <h2>Salary Preview</h2>
                    <p>Review grouped Work Status totals before creating payroll records.</p>
                    <div class="table-wrap">
                        <table>
                            <tr>
                                <th>Employee</th>
                                <th>Client</th>
                                <th>Working Count</th>
                                <th>Payable Count</th>
                                <th>Non Working Count</th>
                                <th>Monthly Salary</th>
                                <th>Payable Salary</th>
                                <th>Existing Payroll?</th>
                                <th>Action</th>
                            </tr>
                            @forelse($workStatusPreviewRows as $index => $row)
                                <tr>
                                    <td>
                                        {{ $row['employee']->name }}<br>
                                        <span style="color:var(--muted);">{{ $row['employee']->employee_id }}</span>
                                        <input type="hidden" name="rows[{{ $index }}][employee_id]" value="{{ $row['employee']->id }}">
                                    </td>
                                    <td>
                                        {{ $row['client']?->company_name ?: 'No Client / Agency Payroll' }}
                                        <input type="hidden" name="rows[{{ $index }}][client_id]" value="{{ $row['client']?->id }}">
                                    </td>
                                    <td>{{ number_format($row['working_count'], 2) }}</td>
                                    <td>
                                        {{ number_format($row['effective_salary_count'], 2) }}
                                        @if($row['cap_applied'])
                                            <br><span class="badge badge-warning">Cap Applied</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($row['non_working_count']) }}</td>
                                    <td>BDT {{ number_format($row['monthly_salary'], 2) }}</td>
                                    <td>BDT {{ number_format($row['payable_salary'], 2) }}</td>
                                    <td>
                                        @if($row['existing_payroll'])
                                            Yes - #{{ $row['existing_payroll']->id }}
                                        @else
                                            No
                                        @endif
                                    </td>
                                    <td>
                                        <select name="rows[{{ $index }}][action]">
                                            @if($row['existing_payroll'])
                                                <option value="skip">Skip</option>
                                                <option value="regenerate">Regenerate</option>
                                            @else
                                                <option value="generate">Generate</option>
                                                <option value="skip">Skip</option>
                                            @endif
                                        </select>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9">No Work Status records found for this filter.</td></tr>
                            @endforelse
                        </table>
                    </div>
                    @if(count($workStatusPreviewRows) > 0)
                        <div class="salary-actions">
                            <button class="btn" type="submit" {{ $duplicateCyclePayroll ? 'disabled' : '' }}>Generate Salary</button>
                        </div>
                    @endif
                </div>
            </form>
        @endif
    </div>

    <div class="card salary-mode-panel" id="manual-mode-card" style="margin-top:20px;">
        <h2>Manual Salary Information</h2>

        <form method="POST" action="/admin/payroll" id="salary-generate-form" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="generation_mode" value="manual">
            <input type="hidden" name="salary_date" value="{{ $quickSalaryDate }}">
            @if($quickSalaryContext)<input type="hidden" name="return_to" value="/admin/payroll?status=due">@endif

            <div class="salary-section">
                <h2>Salary Setup</h2>
                <div class="salary-form-grid salary-setup-grid">
                    <p class="salary-field">Employee<br>
                        <select name="employee_id" id="employee_id" class="employee-payment-select" required>
                            <option value="" data-salary="0">Select Employee</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" data-salary="{{ (float) $employee->monthly_salary }}" {{ (string) old('employee_id', $quickEmployeeId) === (string) $employee->id ? 'selected' : '' }}>
                                    {{ $employee->name }} ({{ $employee->employee_id }}) - BDT {{ number_format($employee->monthly_salary, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </p>

                    <p class="salary-field">Client<br>
                        <select name="client_id" id="client_id">
                            <option value="">No Client / Agency Payroll</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" data-balance="{{ $clientBalances[$client->id] ?? 0 }}" {{ (string) old('client_id', $quickClientId) === (string) $client->id ? 'selected' : '' }}>
                                    {{ $client->company_name }}
                                </option>
                            @endforeach
                        </select>
                    </p>

                    <p class="salary-field">Calculation Type<br>
                        <select name="calculation_type" id="calculation_type" required>
                            <option value="date_to_date" {{ old('calculation_type', data_get($quickSalaryContext, 'calculation_type', 'date_to_date')) === 'date_to_date' ? 'selected' : '' }}>Date To Date</option>
                            <option value="monthly_cycle" {{ old('calculation_type', data_get($quickSalaryContext, 'calculation_type')) === 'monthly_cycle' ? 'selected' : '' }}>Monthly Cycle</option>
                        </select>
                    </p>

                    <p class="salary-field">Salary Month<br><input type="month" name="salary_month" id="salary_month" value="{{ old('salary_month', $quickSalaryMonth) }}"></p>
                    <p class="salary-field">From Date<br><input type="date" name="from_date" id="from_date" value="{{ old('from_date', $quickCycleStart ?: now()->startOfMonth()->toDateString()) }}"></p>
                    <p class="salary-field">To Date<br><input type="date" name="to_date" id="to_date" value="{{ old('to_date', $quickCycleEnd ?: now()->toDateString()) }}"></p>
                    <p class="salary-field">Use Work Status Records<br>
                        <label style="display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" name="use_work_status_records" id="use_work_status_records" value="1" {{ old('use_work_status_records', data_get($quickSalaryContext, 'use_work_status', false)) ? 'checked' : '' }}>
                            Auto-fill working days
                        </label>
                    </p>
                </div>
                <div class="fund-warning" id="fund_warning"></div>
            </div>

            @include('admin.payroll.partials.employee-payment-card')

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
                <button class="btn" type="submit" {{ $duplicateCyclePayroll ? 'disabled' : '' }}>Save Salary</button>
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
        const employeePaymentInfo = @json($employeePaymentInfo ?? []);
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

        const modeTabs = document.querySelectorAll('.mode-tab');
        const workStatusModeCard = document.getElementById('work-status-mode-card');
        const manualModeCard = document.getElementById('manual-mode-card');

        function setSalaryMode(mode) {
            workStatusModeCard.style.display = mode === 'work_status' ? 'block' : 'none';
            manualModeCard.style.display = mode === 'manual' ? 'block' : 'none';
            modeTabs.forEach((tab) => {
                tab.classList.toggle('active-mode', tab.dataset.modeTarget === mode);
            });
        }

        modeTabs.forEach((tab) => {
            tab.addEventListener('click', () => setSalaryMode(tab.dataset.modeTarget));
        });

        setSalaryMode(@json($activeMode));

        function hasBankInformation(info) {
            return Boolean(info?.bank_name && info?.account_name && info?.account_number);
        }

        function paymentInfoText(info) {
            return [
                `Employee: ${info?.name || '-'}`,
                '',
                `Bank: ${info?.bank_name || '-'}`,
                `Account Name: ${info?.account_name || '-'}`,
                `Account Number: ${info?.account_number || '-'}`,
                `Branch: ${info?.branch_name || '-'}`,
            ].join('\n');
        }

        function copyText(text) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text);
            }

            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            document.execCommand('copy');
            textarea.remove();

            return Promise.resolve();
        }

        function updateEmployeePaymentCards(sourceSelect = null) {
            const selectedEmployeeId = sourceSelect?.value || employeeSelect?.value || '';
            const info = employeePaymentInfo[selectedEmployeeId] || null;
            const bankReady = hasBankInformation(info);

            document.querySelectorAll('[data-payment-info-card]').forEach((card) => {
                card.classList.toggle('is-visible', Boolean(info));
                if (!info) {
                    return;
                }

                card.querySelector('[data-payment-field="employee"]').textContent = info.name || '-';
                card.querySelector('[data-payment-field="employee_id"]').textContent = info.employee_id || '-';
                card.querySelector('[data-payment-field="status"]').textContent = info.status || '-';
                card.querySelector('[data-payment-field="joining_date"]').textContent = info.joining_date || '-';
                card.querySelector('[data-payment-field="bank_name"]').textContent = info.bank_name || '-';
                card.querySelector('[data-payment-field="account_name"]').textContent = info.account_name || '-';
                card.querySelector('[data-payment-field="account_number"]').textContent = info.account_number || '-';
                card.querySelector('[data-payment-field="branch_name"]').textContent = info.branch_name || '-';
                card.querySelector('[data-payment-field="routing_number"]').textContent = info.routing_number || '-';
                card.querySelector('[data-payment-warning]').classList.toggle('is-visible', !bankReady);
                card.querySelectorAll('[data-copy-payment]').forEach((button) => {
                    button.disabled = !bankReady;
                    button.dataset.employeeId = selectedEmployeeId;
                });
            });
        }

        document.querySelectorAll('.employee-payment-select').forEach((select) => {
            select.addEventListener('change', () => updateEmployeePaymentCards(select));
            select.addEventListener('input', () => updateEmployeePaymentCards(select));
        });

        document.querySelectorAll('[data-copy-payment]').forEach((button) => {
            button.addEventListener('click', () => {
                const info = employeePaymentInfo[button.dataset.employeeId] || null;
                if (!hasBankInformation(info)) {
                    return;
                }

                const text = button.dataset.copyPayment === 'account'
                    ? info.account_number
                    : paymentInfoText(info);

                copyText(text).then(() => {
                    const original = button.textContent;
                    button.textContent = 'Copied';
                    setTimeout(() => button.textContent = original, 1400);
                });
            });
        });

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
        const initialPaymentSelect = Array.from(document.querySelectorAll('.employee-payment-select')).find((select) => select.value) || employeeSelect;
        updateEmployeePaymentCards(initialPaymentSelect);
        calculateSalary();
    </script>
@endsection
