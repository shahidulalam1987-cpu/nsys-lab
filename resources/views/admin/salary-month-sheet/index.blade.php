@extends('layouts.admin')

@section('content')
    <h1>Salary Report</h1>

    <div class="card">
        <form method="GET" action="/admin/salary-month-sheet">
            <label>Salary Month<br><input type="month" name="month" value="{{ request('month') }}"></label>
            <label>Payment Month<br><input type="month" name="payment_month" value="{{ request('payment_month') }}"></label>

            <select name="employee_id">
                <option value="">All Employees</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                        {{ $employee->name }} ({{ $employee->employee_id }})
                    </option>
                @endforeach
            </select>

            <select name="client_id">
                <option value="">All Clients</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected((string)request('client_id') === (string)$client->id)>{{ $client->company_name }}</option>
                @endforeach
            </select>

            <select name="status">
                <option value="">All Status</option>
                @foreach(['generated' => 'Generated', 'unpaid' => 'Unpaid', 'partial' => 'Partially Paid', 'paid' => 'Paid', 'final_settlement' => 'Final Settlement', 'reversed' => 'Reversed'] as $value => $label)
                    <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <select name="salary_source">
                <option value="">All Salary Sources</option>
                @foreach(\App\Models\Employee::SALARY_SOURCES as $value => $label)
                    <option value="{{ $value }}" @selected(request('salary_source') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="payment_source">
                <option value="">All Payment Sources</option>
                @foreach(['finance_ledger_linked' => 'Finance Ledger Linked', 'legacy_manual_paid' => 'Legacy Manual Paid', 'reversed' => 'Reversed', 'superseded' => 'Superseded'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('payment_source') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="history_scope">
                @foreach(['current' => 'Current Payrolls', 'historical' => 'Historical/Superseded Payrolls', 'all' => 'All Payrolls'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('history_scope', 'current') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <button class="btn" type="submit">Filter</button>
            <a href="/admin/salary-month-sheet">Reset</a>
            <a class="btn" href="/admin/salary-month-sheet/export?{{ http_build_query(request()->only(['month', 'payment_month', 'employee_id', 'client_id', 'status', 'salary_source', 'payment_source', 'history_scope'])) }}">Export CSV</a>
            <a class="btn" href="/admin/salary-month-sheet/export/excel?{{ http_build_query(request()->only(['month', 'payment_month', 'employee_id', 'client_id', 'status', 'salary_source', 'payment_source', 'history_scope'])) }}">Export Excel</a>
            <p style="margin:10px 5px 0;flex-basis:100%;">Salary Month = the month salary belongs to. Payment Month = the month salary was actually paid.</p>
        </form>
    </div>

    @if($integrity['legacy_paid_without_ledger_count'] > 0)
        <a class="card" href="/admin/salary-month-sheet?payment_source=legacy_manual_paid&history_scope=current" style="display:block;text-decoration:none;border-color:var(--warning);">
            <strong>Integrity Warning</strong>
            <p style="margin-bottom:0;">Legacy Paid Without Ledger: {{ number_format($integrity['legacy_paid_without_ledger_count']) }} | Amount: BDT {{ number_format($integrity['legacy_paid_without_ledger_amount'], 2) }}</p>
        </a>
    @endif

    @if($historyScope === 'all')
        <div class="card" style="border-color:var(--warning);">
            Historical payrolls may include regenerated/superseded records and should not be double-counted in current liabilities.
        </div>
    @endif

    <div class="stats-grid">
        <div class="stat-card">
            <p>Total Salary Records</p>
            <h2>{{ number_format($summary['total_salary_records']) }}</h2>
        </div>
        <div class="stat-card">
            <p>Total Payable Salary (BDT)</p>
            <h2>BDT {{ number_format($summary['total_payable_salary'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Total Paid Salary</p>
            <h2>BDT {{ number_format($summary['total_paid_salary'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Total Remaining Due</p>
            <h2>BDT {{ number_format($summary['total_remaining_due'], 2) }}</h2>
        </div>
    </div>

    <div class="card">
        <h2>{{ $month ? 'Salary Month: ' . $month->format('F Y') : (request('payment_month') ? 'Payment Month: ' . \Carbon\Carbon::createFromFormat('Y-m', request('payment_month'))->format('F Y') : 'All Payroll History') }}</h2>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>Employee</th>
                    <th>Client</th>
                    <th>Salary Month</th>
                    <th>Salary Period</th>
                    <th>Working Days</th>
                    <th>Payable Salary (BDT)</th>
                    <th>Paid Salary</th>
                    <th>Remaining Due</th>
                    <th>Status</th>
                    <th>Payment Source Status</th>
                    <th>Payment Information</th>
                    <th>Payment Date</th>
                </tr>

                @forelse($rows as $payroll)
                    <tr>
                        <td>
                            @if($payroll->employee)
                                <a href="/admin/employees/{{ $payroll->employee->id }}">{{ $payroll->employee->employee_id }}</a>
                                <br>{{ $payroll->employee->name }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $payroll->client?->company_name ?: '-' }}</td>
                        <td>{{ $payroll->salary_month?->format('F Y') ?: '-' }}</td>
                        <td>{{ $payroll->salary_period }}</td>
                        <td>{{ $payroll->working_days ?? '-' }}</td>
                        <td>BDT {{ number_format($payroll->payable_salary, 2) }}</td>
                        <td>BDT {{ number_format($payroll->paid_amount, 2) }}</td>
                        <td>BDT {{ number_format(max($payroll->payable_salary - $payroll->paid_amount, 0), 2) }}</td>
                        <td><span class="badge {{ $payroll->reportStatusKey() === 'paid' ? 'badge-success' : ($payroll->reportStatusKey() === 'reversed' ? 'badge-neutral' : 'badge-warning') }}">{{ $payroll->reportStatusLabel() }}</span></td>
                        @php
                            $paymentSourceKey = $payroll->paymentSourceStatusKey();
                            $paymentSourceClass = match ($paymentSourceKey) {
                                'finance_ledger_linked' => 'badge-success',
                                'legacy_manual_paid' => 'badge-warning',
                                'reversed', 'superseded' => 'badge-neutral',
                                default => 'badge-info',
                            };
                        @endphp
                        <td><span class="badge {{ $paymentSourceClass }}" title="{{ $payroll->paymentSourceStatusHelp() }}">{{ $payroll->paymentSourceStatusLabel() }}</span></td>
                        <td>
                            <details>
                                <summary>Payment Information</summary>
                                <p style="margin:8px 0 0;">
                                    <strong>Bank:</strong> {{ $payroll->snapshotBankName() }}<br>
                                    <strong>Account Name:</strong> {{ $payroll->snapshotAccountName() }}<br>
                                    <strong>Account Number:</strong> {{ $payroll->snapshotAccountNumber() }}<br>
                                    <strong>Branch:</strong> {{ $payroll->snapshotBranchName() }}<br>
                                    <strong>Finance Account:</strong> {{ $payroll->finance_account_name ?: ($payroll->financeAccount?->account_name ?: '-') }}<br>
                                    <strong>Reference:</strong> {{ $payroll->transaction_id ?: '-' }}
                                </p>
                            </details>
                        </td>
                        <td>{{ $payroll->payment_date?->toDateString() ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12">{{ $month || request('payment_month') ? 'No generated salary records found for the selected month.' : 'No salary history found.' }}</td>
                    </tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
