@extends('layouts.admin')

@section('content')
    @php
        $isUpcoming = $mode === 'upcoming';
        $pageTitle = $isUpcoming ? 'Upcoming Salary' : 'Unpaid Salary';
    @endphp

    <h1>{{ $pageTitle }}</h1>
    <p>{{ $isUpcoming ? 'Salary dates within the next five days. This is a notification-only stage.' : 'Complete work status, salary generation, approval, and payment from this queue.' }}</p>

    @if(! $isUpcoming && ($filters['employee_scope'] ?? '') === 'terminated')
        <div class="card"><strong>Terminated Final Settlement</strong><br><span class="muted">Final salary work that still needs generation or payment.</span></div>
    @endif

    <div class="card">
        <form method="GET" action="/admin/payroll" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
            <input type="hidden" name="status" value="{{ $isUpcoming ? 'upcoming' : 'due' }}">
            <label>Month<br><input type="month" name="month" value="{{ $filters['month'] ?? '' }}"></label>
            <label>Employee<br>
                <select name="employee_id">
                    <option value="">All Employees</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((string)($filters['employee_id'] ?? '') === (string)$employee->id)>{{ $employee->name }} ({{ $employee->employee_id }})</option>
                    @endforeach
                </select>
            </label>
            <label>Salary Source<br>
                <select name="salary_source">
                    <option value="">All Salary Sources</option>
                    @foreach(\App\Models\Employee::SALARY_SOURCES as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['salary_source'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            @unless($isUpcoming)
                <label>Employee Scope<br>
                    <select name="employee_scope">
                        <option value="all">All Employees</option>
                        <option value="active" @selected(($filters['employee_scope'] ?? '') === 'active')>Active Employees</option>
                        <option value="terminated" @selected(($filters['employee_scope'] ?? '') === 'terminated')>Terminated Final Settlement</option>
                    </select>
                </label>
            @endunless
            <button class="btn" type="submit">Filter</button>
            <a href="/admin/payroll?status={{ $isUpcoming ? 'upcoming' : 'due' }}">Reset</a>
        </form>
    </div>

    <div class="card table-wrap">
        <table>
            @if($isUpcoming)
                <tr><th>Employee</th><th>Client</th><th>Salary Date</th><th>Days Remaining</th><th>Expected Salary</th><th>Work Status Count</th><th>Status</th></tr>
            @else
                <tr><th>Employee</th><th>Client</th><th>Salary Date</th><th>Amount</th><th>Current Stage</th><th>Overdue Days</th><th>Action</th></tr>
            @endif

            @forelse($stageRows as $row)
                @php
                    $employee = $row['employee'];
                    $stage = $row['stage'];
                    $payroll = data_get($stage, 'payroll');
                    $estimate = data_get($stage, 'estimate', []);
                    $salaryDate = data_get($stage, 'salary_date') ?: $payroll?->salaryDueDate();
                    $amount = $payroll
                        ? max((float)$payroll->payable_salary - (float)$payroll->paid_amount, 0)
                        : (float)data_get($estimate, 'estimated_payable_salary', 0);
                    $client = $payroll?->client ?: $employee->activeAssignments->first()?->client;
                    $category = $stage['category'];
                    $stageLabel = $stage['label'];
                    if ($payroll?->isFinalSettlementPayroll() && (float)$payroll->paid_amount > 0 && (float)$payroll->paid_amount < (float)$payroll->payable_salary) {
                        $stageLabel = 'Final Settlement Partial';
                    }
                    $overdueDays = $salaryDate && $salaryDate->lt(today()) ? $salaryDate->diffInDays(today()) : 0;
                @endphp
                <tr>
                    <td><a href="/admin/employees/{{ $employee->id }}">{{ $employee->employee_id }}</a><br><strong>{{ $employee->name }}</strong></td>
                    <td>{{ $client?->company_name ?: '-' }}</td>
                    <td>{{ $salaryDate?->toDateString() ?: '-' }}</td>
                    @if($isUpcoming)
                        <td>{{ $salaryDate ? today()->diffInDays($salaryDate) : '-' }}</td>
                        <td>BDT {{ number_format($amount, 2) }}</td>
                        <td>{{ number_format((float)data_get($estimate, 'actual_work_status_count', data_get($estimate, 'working_salary_count', 0)), 2) }}</td>
                        <td><span class="badge badge-info">Upcoming Salary</span></td>
                    @else
                        <td>
                            @if(! $payroll)
                                <small class="muted">Estimated Amount Due</small><br>
                            @endif
                            BDT {{ number_format($amount, 2) }}
                            @if(! $payroll)
                                <br><small class="muted">{{ (int)data_get($estimate, 'work_status_records', 0) > 0 ? 'Based on Work Status' : 'Work Status Missing' }}</small>
                                <br><small class="muted">Working: {{ number_format((float)data_get($estimate, 'actual_work_status_count', data_get($estimate, 'working_salary_count', 0)), 2) }}</small>
                                <br><small class="muted">Payable Count: {{ number_format((float)data_get($estimate, 'effective_salary_count', data_get($estimate, 'working_salary_count', 0)), 2) }}</small>
                                <br><small class="muted">Non Working: {{ number_format((float)data_get($estimate, 'non_working_count', 0), 2) }}</small>
                            @elseif($payroll->isFinalSettlementPayroll())
                                <br><small class="muted">Working: {{ number_format((float)$payroll->working_days, 2) }}</small>
                                <br><small class="muted">Non Working: {{ number_format((float)$payroll->non_working_days, 2) }}</small>
                            @endif
                        </td>
                        <td><span class="badge {{ in_array($category, ['unpaid', 'final_settlement_unpaid']) ? 'badge-danger' : 'badge-warning' }}">{{ $stageLabel }}</span></td>
                        <td>
                            @if($overdueDays > 0)
                                {{ $employee->status === 'terminated' ? 'Final Settlement Overdue: ' . $overdueDays . ' Days' : $overdueDays . ' Days Overdue' }}
                            @else
                                -
                            @endif
                            @if($category === \App\Services\PayrollCategoryService::FINAL_SETTLEMENT_PENDING)
                                <br><small class="muted">Final salary not generated yet</small>
                            @endif
                        </td>
                        <td>
                            @if($category === \App\Services\PayrollCategoryService::PENDING_WORK_STATUS || ($category === \App\Services\PayrollCategoryService::FINAL_SETTLEMENT_PENDING && (int)data_get($estimate, 'work_status_records', 0) === 0))
                                @php
                                    $query = ['entry_mode' => 'monthly', 'employee_id' => $employee->id, 'salary_month' => $salaryDate?->format('Y-m'), 'status' => 'working', 'note' => 'Salary cycle work status entry', 'return_to' => '/admin/payroll?status=due'];
                                    if (! $employee->isAgencyInternal() && $client) { $query['client_id'] = $client->id; }
                                @endphp
                                <a class="btn" href="/admin/work-status/create?{{ http_build_query(array_filter($query)) }}">Add Work Status</a>
                            @elseif(in_array($category, [\App\Services\PayrollCategoryService::SALARY_READY, \App\Services\PayrollCategoryService::FINAL_SETTLEMENT_PENDING], true))
                                @php
                                    $generateQuery = [
                                        'employee_id' => $employee->id,
                                        'client_id' => $client?->id,
                                        'salary_date' => $salaryDate?->toDateString(),
                                        'cycle_start' => data_get($estimate, 'salary_period_start')?->toDateString(),
                                        'cycle_end' => data_get($estimate, 'salary_period_end')?->toDateString(),
                                        'calculation_type' => 'date_to_date',
                                        'use_work_status' => 1,
                                    ];
                                @endphp
                                <a class="btn" href="/admin/payroll/create?{{ http_build_query(array_filter($generateQuery, fn ($value) => $value !== null && $value !== '')) }}">{{ $employee->status === 'terminated' ? 'Generate Final Salary' : 'Generate Salary' }}</a>
                            @elseif($payroll)
                                <a href="/admin/payroll/{{ $payroll->id }}">View</a> |
                                <a href="/admin/payroll/{{ $payroll->id }}/edit">Edit</a>
                                @if($payroll->canMarkPaid() && $payroll->payroll_status !== 'paid')
                                    | <button class="btn" type="button" onclick="document.getElementById('confirm-payment-{{ $payroll->id }}').style.display='flex';">Confirm Payment</button>
                                @endif
                            @endif
                        </td>
                    @endif
                </tr>

                @if(! $isUpcoming && $payroll?->canMarkPaid() && $payroll->payroll_status !== 'paid')
                    <tr><td colspan="7" style="padding:0;border:0;">
                        <div id="confirm-payment-{{ $payroll->id }}" style="display:none;position:fixed;inset:0;background:rgba(2,6,23,.74);z-index:100;align-items:center;justify-content:center;padding:20px;">
                            <div class="card" style="max-width:720px;width:100%;">
                                <h2>Confirm Payment</h2>
                                <p>{{ $payroll->snapshotEmployeeName() }} | Payable BDT {{ number_format($payroll->payable_salary, 2) }}</p>
                                <form method="POST" action="/admin/payroll/{{ $payroll->id }}/confirm-payment" enctype="multipart/form-data">
                                    @csrf
                                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
                                        <label>Payment Date<br><input type="date" name="payment_date" value="{{ today()->toDateString() }}" required></label>
                                        <label>Finance Account<br><select name="finance_account_id" required><option value="">Select Account</option>@foreach($financeAccounts as $account)<option value="{{ $account->id }}">{{ $account->account_name }} - BDT {{ number_format($account->current_balance, 2) }}</option>@endforeach</select></label>
                                        <label>Transaction Reference<br><input type="text" name="transaction_id" required></label>
                                        <label>Payment Proof<br><input type="file" name="salary_payment_attachment" accept="image/*"></label>
                                        <label style="grid-column:1/-1;">Payment Note<br><textarea name="payment_note" required>Salary payment for {{ $payroll->salary_month?->format('F Y') }}</textarea></label>
                                    </div>
                                    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:12px;"><button type="button" class="btn" onclick="document.getElementById('confirm-payment-{{ $payroll->id }}').style.display='none';">Cancel</button><button class="btn" type="submit">Confirm Payment</button></div>
                                </form>
                            </div>
                        </div>
                    </td></tr>
                @endif
            @empty
                <tr><td colspan="7">{{ $isUpcoming ? 'No upcoming salaries found.' : 'No unpaid salary work found.' }}</td></tr>
            @endforelse
        </table>
    </div>
@endsection
