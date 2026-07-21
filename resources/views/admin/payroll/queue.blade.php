@extends('layouts.admin')

@section('content')
    @php
        $isUpcoming = $mode === 'upcoming';
        $isFinalSettlementQueue = ! $isUpcoming && ($filters['employee_scope'] ?? '') === 'terminated';
        $pageTitle = $isUpcoming ? 'Upcoming Salary' : ($isFinalSettlementQueue ? 'Final Settlement Queue' : 'Payroll Action Queue');
    @endphp

    <style>
        .settlement-workspace { display: grid; gap: 14px; }
        .settlement-row { border: 1px solid rgba(148,163,184,.22); border-radius: 12px; background: rgba(15,23,42,.62); padding: 16px; }
        .settlement-row-top { display: grid; grid-template-columns: minmax(220px,1.2fr) minmax(180px,.8fr) minmax(240px,1fr) minmax(220px,1fr); gap: 14px; align-items: start; }
        .settlement-person strong { color: #e5efff; font-size: 15px; }
        .settlement-code { color: #93c5fd; font-weight: 700; font-size: 12px; letter-spacing: .02em; }
        .settlement-meta { color: #94a3b8; font-size: 12px; line-height: 1.6; }
        .settlement-label { color: #94a3b8; font-size: 11px; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 4px; }
        .settlement-value { color: #e2e8f0; font-weight: 700; }
        .settlement-period { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 8px; }
        .settlement-period span { background: rgba(2,6,23,.4); border: 1px solid rgba(148,163,184,.18); border-radius: 8px; padding: 8px; white-space: nowrap; }
        .settlement-arrow { color: #38bdf8; font-weight: 800; }
        .settlement-schedule { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 8px; }
        .settlement-mini { background: rgba(2,6,23,.35); border: 1px solid rgba(148,163,184,.16); border-radius: 8px; padding: 8px; min-height: 62px; }
        .settlement-mini small { color: #94a3b8; display: block; margin-bottom: 4px; }
        .settlement-status { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .settlement-body { display: grid; grid-template-columns: minmax(220px,.8fr) minmax(220px,1fr) minmax(260px,1.2fr) minmax(180px,.7fr); gap: 14px; margin-top: 14px; align-items: stretch; }
        .settlement-panel { background: rgba(2,6,23,.28); border: 1px solid rgba(148,163,184,.14); border-radius: 10px; padding: 12px; }
        .settlement-money { font-size: 18px; color: #e2e8f0; font-weight: 800; }
        .settlement-counts { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
        .settlement-counts span { background: rgba(30,41,59,.8); border: 1px solid rgba(148,163,184,.16); border-radius: 999px; padding: 5px 8px; color: #cbd5e1; font-size: 12px; }
        .settlement-progress { display: grid; gap: 7px; }
        .settlement-step { display: flex; align-items: center; gap: 8px; color: #94a3b8; font-size: 12px; }
        .settlement-dot { width: 18px; height: 18px; border-radius: 999px; border: 1px solid rgba(148,163,184,.35); display: inline-flex; align-items: center; justify-content: center; font-size: 11px; flex: 0 0 auto; }
        .settlement-step.done { color: #bbf7d0; }
        .settlement-step.done .settlement-dot { background: #16a34a; border-color: #16a34a; color: #fff; }
        .settlement-step.current { color: #fde68a; }
        .settlement-step.current .settlement-dot { background: #f59e0b; border-color: #f59e0b; color: #111827; }
        .settlement-actions { display: flex; flex-direction: column; gap: 8px; align-items: flex-start; }
        .settlement-helper { color: #94a3b8; font-size: 12px; line-height: 1.5; }
        .payroll-filter-grid { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); align-items: end; }
        .payroll-filter-grid label { color: var(--muted); font-size: 12px; font-weight: 700; }
        .payroll-filter-grid input, .payroll-filter-grid select { margin: 6px 0 0; width: 100%; }
        .payroll-filter-actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .upcoming-summary { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
        .upcoming-summary-item { background: rgba(15,23,42,.42); border: 1px solid rgba(148,163,184,.16); border-radius: 10px; padding: 12px; }
        .upcoming-summary-item span { color: #94a3b8; display: block; font-size: 12px; margin-bottom: 4px; }
        .upcoming-summary-item strong { color: #e5efff; font-size: 20px; }
        .queue-summary { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
        .queue-summary-item { background: rgba(15,23,42,.42); border: 1px solid rgba(148,163,184,.16); border-radius: 10px; padding: 12px; }
        .queue-summary-item span { color: #94a3b8; display: block; font-size: 12px; margin-bottom: 4px; }
        .queue-summary-item strong { color: #e5efff; font-size: 20px; }
        .badge-neutral { background: #64748b; }
        .badge-info { background: #2563eb; }
        @media (max-width: 1180px) {
            .settlement-row-top, .settlement-body { grid-template-columns: repeat(2,minmax(0,1fr)); }
        }
        @media (max-width: 760px) {
            .settlement-row-top, .settlement-body, .settlement-schedule { grid-template-columns: 1fr; }
            .settlement-period { grid-template-columns: 1fr; }
            .settlement-arrow { display: none; }
        }
    </style>

    <h1>{{ $pageTitle }}</h1>
    <p>{{ $isUpcoming ? 'Salary dates within the next five days. This is a notification-only stage.' : ($isFinalSettlementQueue ? 'Review final settlement status, work status readiness, salary schedule, and payment progress from one workspace.' : 'Complete work status, salary generation, approval, and payment from one action queue.') }}</p>

    @if($isFinalSettlementQueue)
        <div class="card">
            <strong>Final Settlement Workspace</strong><br>
            <span class="muted">Settlement period, salary date, payment deadline, work status, and action readiness are prepared by the payroll services before this page renders.</span>
        </div>
    @endif

    <div class="card">
        <form method="GET" action="/admin/payroll" class="payroll-filter-grid">
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
                <label>Queue Stage<br>
                    <select name="queue_stage">
                        <option value="">All Action Stages</option>
                        <option value="pending_work_status" @selected(($filters['queue_stage'] ?? '') === 'pending_work_status')>Pending Work Status</option>
                        <option value="salary_ready" @selected(($filters['queue_stage'] ?? '') === 'salary_ready')>Salary Ready</option>
                        <option value="generated" @selected(($filters['queue_stage'] ?? '') === 'generated')>Pending Approval</option>
                        <option value="unpaid" @selected(($filters['queue_stage'] ?? '') === 'unpaid')>Unpaid</option>
                        <option value="final_settlement_pending" @selected(($filters['queue_stage'] ?? '') === 'final_settlement_pending')>Final Settlement Pending</option>
                        <option value="final_settlement_unpaid" @selected(($filters['queue_stage'] ?? '') === 'final_settlement_unpaid')>Final Settlement Unpaid</option>
                    </select>
                </label>
            @endunless
            <div class="payroll-filter-actions">
                <button class="btn" type="submit">Filter</button>
                <a href="/admin/payroll?status={{ $isUpcoming ? 'upcoming' : 'due' }}{{ $isFinalSettlementQueue ? '&employee_scope=terminated' : '' }}">Reset</a>
            </div>
        </form>
    </div>

    @if($isUpcoming)
        <div class="card upcoming-summary">
            <div class="upcoming-summary-item">
                <span>Upcoming Employees</span>
                <strong>{{ number_format($stageRows->count()) }}</strong>
            </div>
            <div class="upcoming-summary-item">
                <span>Total Estimated Amount</span>
                <strong>BDT {{ number_format($stageRows->sum(fn ($row) => (float) data_get($row, 'stage.estimate.estimated_payable_salary', 0)), 2) }}</strong>
            </div>
            <div class="upcoming-summary-item">
                <span>Reminder Window</span>
                <strong>Next 5 Days</strong>
            </div>
        </div>
        <div class="card table-wrap">
            <table>
                <tr><th>Employee</th><th>Client</th><th>Salary Date</th><th>Days Remaining</th><th>Estimated Salary</th><th>Work Status Count</th><th>Status</th></tr>
                @forelse($stageRows as $row)
                    @php
                        $display = $row['display'];
                        $stage = $row['stage'];
                        $estimate = data_get($stage, 'estimate', []);
                    @endphp
                    <tr>
                        <td><a href="{{ $display['employee_url'] }}">{{ $display['employee_code'] }}</a><br><strong>{{ $display['employee_name'] }}</strong></td>
                        <td>{{ $display['client_name'] }}</td>
                        <td>{{ $display['settlement_salary_date'] }}</td>
                        <td>{{ $display['days_remaining_label'] }}</td>
                        <td>{{ $display['estimated_salary_label'] }}</td>
                        <td>{{ number_format((float)data_get($estimate, 'actual_work_status_count', data_get($estimate, 'working_salary_count', 0)), 2) }}</td>
                        <td><span class="badge badge-info">Upcoming Salary</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7">No upcoming salaries found.</td></tr>
                @endforelse
            </table>
        </div>
    @else
        @php
            $queueSummary = $isFinalSettlementQueue
                ? [
                    'Final Settlement Pending' => $stageRows->where('stage.category', \App\Services\PayrollCategoryService::FINAL_SETTLEMENT_PENDING)->count(),
                    'Final Settlement Unpaid' => $stageRows->where('stage.category', \App\Services\PayrollCategoryService::FINAL_SETTLEMENT_UNPAID)->count(),
                    'Work Status Needed' => $stageRows->filter(fn ($row) => data_get($row, 'stage.category') === \App\Services\PayrollCategoryService::FINAL_SETTLEMENT_PENDING && ! data_get($row, 'display.has_work_status'))->count(),
                    'Ready to Generate' => $stageRows->filter(fn ($row) => data_get($row, 'display.generate_salary_url'))->count(),
                    'Payment Pending' => $stageRows->filter(fn ($row) => data_get($row, 'stage.category') === \App\Services\PayrollCategoryService::FINAL_SETTLEMENT_UNPAID)->count(),
                ]
                : [
                    'Pending Work Status' => $stageRows->where('stage.category', \App\Services\PayrollCategoryService::PENDING_WORK_STATUS)->count(),
                    'Salary Ready' => $stageRows->where('stage.category', \App\Services\PayrollCategoryService::SALARY_READY)->count(),
                    'Pending Approval' => $stageRows->where('stage.category', \App\Services\PayrollCategoryService::GENERATED)->count(),
                    'Unpaid' => $stageRows->where('stage.category', \App\Services\PayrollCategoryService::UNPAID)->count(),
                    'Final Settlement Due' => $stageRows->filter(fn ($row) => in_array(data_get($row, 'stage.category'), [\App\Services\PayrollCategoryService::FINAL_SETTLEMENT_PENDING, \App\Services\PayrollCategoryService::FINAL_SETTLEMENT_UNPAID], true))->count(),
                ];
        @endphp
        <div class="card queue-summary">
            @foreach($queueSummary as $label => $count)
                <div class="queue-summary-item">
                    <span>{{ $label }}</span>
                    <strong>{{ number_format($count) }}</strong>
                </div>
            @endforeach
        </div>
        <div class="settlement-workspace">
            @forelse($stageRows as $row)
                @php
                    $display = $row['display'];
                    $employee = $row['employee'];
                    $stage = $row['stage'];
                    $payroll = data_get($stage, 'payroll');
                    $isFinalRow = $employee->status === 'terminated';
                @endphp

                <div class="settlement-row">
                    <div class="settlement-row-top">
                        <div class="settlement-person">
                            <div class="settlement-label">Employee Summary</div>
                            <a class="settlement-code" href="{{ $display['employee_url'] }}">{{ $display['employee_code'] }}</a><br>
                            <strong>{{ $display['employee_name'] }}</strong>
                            <div class="settlement-meta">
                                {{ $display['department'] }} | {{ $display['role'] }}<br>
                                {{ $display['employment_type'] }} | <span class="badge badge-neutral">{{ $display['current_status'] }}</span>
                            </div>
                        </div>

                        <div>
                            <div class="settlement-label">Client</div>
                            <div class="settlement-value">{{ $display['client_name'] }}</div>
                            @if($display['legacy_metadata'])
                                <div style="margin-top:8px;">
                                    <span class="badge badge-warning" title="Resolved using compatibility mapping.">Legacy Payroll Metadata</span>
                                </div>
                            @endif
                        </div>

                        <div>
                            <div class="settlement-label">{{ $isFinalRow ? 'Termination Summary' : 'Payroll Cycle' }}</div>
                            @if($isFinalRow)
                                <div class="settlement-meta">Last Working Date: <strong>{{ $display['last_working_date'] }}</strong></div>
                                <div class="settlement-period" title="Final settlement period is resolved by payroll cycle services.">
                                    <span>{{ $display['settlement_period_start'] }}</span>
                                    <b class="settlement-arrow">-&gt;</b>
                                    <span>{{ $display['settlement_period_end'] }}</span>
                                </div>
                            @else
                                <div class="settlement-meta">Regular payroll queue item</div>
                            @endif
                        </div>

                        <div>
                            <div class="settlement-label">Current Status</div>
                            <div class="settlement-status">
                                <span class="badge {{ $display['stage_badge_class'] }}">{{ $display['stage_label'] }}</span>
                                <span class="badge {{ $display['deadline_badge_class'] }}">{{ $display['deadline_label'] }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="settlement-body">
                        <div class="settlement-panel">
                            <div class="settlement-label">Salary Schedule</div>
                            <div class="settlement-schedule">
                                <div class="settlement-mini"><small>Salary Day</small><strong>{{ $display['salary_day'] }}</strong></div>
                                <div class="settlement-mini" title="The official salary date for this final settlement cycle."><small>{{ $isFinalRow ? 'Settlement Salary Date' : 'Salary Date' }}</small><strong>{{ $display['settlement_salary_date'] }}</strong></div>
                                <div class="settlement-mini" title="{{ $isFinalRow ? 'The last date allowed for settlement payment before it becomes overdue.' : 'Regular payroll is tracked from the salary date.' }}"><small>{{ $isFinalRow ? 'Payment Deadline' : 'Payment Due' }}</small><strong>{{ $isFinalRow ? $display['payment_deadline'] : $display['settlement_salary_date'] }}</strong></div>
                            </div>
                        </div>

                        <div class="settlement-panel">
                            <div class="settlement-label">Estimated Salary</div>
                            <div class="settlement-money">{{ $display['estimated_salary_label'] }}</div>
                            <div class="settlement-helper">{{ $display['estimated_salary_help'] }}</div>
                        </div>

                        <div class="settlement-panel">
                            <div class="settlement-label">Work Status</div>
                            <div class="settlement-value">{{ $display['work_status_label'] }}</div>
                            @if(! $display['has_work_status'])
                                <div class="settlement-helper">{{ $display['work_status_help'] }}</div>
                            @else
                                <div class="settlement-counts">
                                    <span>Working {{ number_format($display['working_count'], 2) }}</span>
                                    <span>Payable {{ number_format($display['payable_count'], 2) }}</span>
                                    <span>Non Working {{ number_format($display['non_working_count'], 2) }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="settlement-panel">
                            <div class="settlement-label">Action</div>
                            <div class="settlement-actions">
                                @if($display['add_work_status_url'])
                                    <a class="btn" href="{{ $display['add_work_status_url'] }}">Add Work Status</a>
                                    <span class="settlement-helper">{{ $isFinalRow ? 'Add Work Status only for the final settlement period.' : 'Add Work Status for this salary cycle.' }}</span>
                                @elseif($display['generate_salary_url'])
                                    <a class="btn" href="{{ $display['generate_salary_url'] }}">{{ $isFinalRow ? 'Generate Final Salary' : 'Generate Salary' }}</a>
                                @elseif($payroll)
                                    <a class="btn" href="{{ $display['payroll_view_url'] }}">View Payroll</a>
                                    <a href="{{ $display['payroll_edit_url'] }}">Edit</a>
                                    @if($display['can_confirm_payment'])
                                        <button class="btn" type="button" onclick="document.getElementById('confirm-payment-{{ $payroll->id }}').style.display='flex';">Confirm Payment</button>
                                    @endif
                                @else
                                    <span class="settlement-helper">No action available.</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($isFinalRow)
                        <div class="settlement-panel" style="margin-top:14px;">
                            <div class="settlement-label">Final Settlement Progress</div>
                            <div class="settlement-progress">
                                @foreach($display['progress_steps'] as $step)
                                    <div class="settlement-step {{ $step['state'] }}">
                                        <span class="settlement-dot">{{ $step['state'] === 'done' ? 'OK' : ($step['state'] === 'current' ? '*' : '') }}</span>
                                        <span>{{ $step['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                @if($payroll?->canMarkPaid() && $payroll->payroll_status !== 'paid' && auth()->user()->hasPermission('payroll.pay'))
                    <div id="confirm-payment-{{ $payroll->id }}" style="display:none;position:fixed;inset:0;background:rgba(2,6,23,.74);z-index:100;align-items:center;justify-content:center;padding:20px;">
                        <div class="card" style="max-width:720px;width:100%;">
                            <h2>Confirm Payment</h2>
                            <p>{{ $payroll->snapshotEmployeeName() }} | Payable BDT {{ number_format($payroll->payable_salary, 2) }}</p>
                            @php
                                $salaryFundBalance = $payroll->client ? (float) $payroll->client->salary_fund_balance() : null;
                                $projectedSalaryFundBalance = $salaryFundBalance !== null
                                    ? $salaryFundBalance - (float) $payroll->payable_salary
                                    : null;
                            @endphp
                            @if($salaryFundBalance !== null && $projectedSalaryFundBalance < 0)
                                <div class="card" style="background:rgba(245,158,11,.14);border-color:#f59e0b;color:#fde68a;margin:12px 0;">
                                    <strong>Client Employee Salary Fund is insufficient.</strong>
                                    <p>After payment the balance will become BDT {{ number_format($projectedSalaryFundBalance, 2) }}. This amount will be tracked as Due From Client.</p>
                                    <div class="stats-grid">
                                        <div class="stat-card"><p>Current Salary Fund Balance</p><h2>BDT {{ number_format($salaryFundBalance, 2) }}</h2></div>
                                        <div class="stat-card"><p>Salary Payment Amount</p><h2>BDT {{ number_format($payroll->payable_salary, 2) }}</h2></div>
                                        <div class="stat-card"><p>Projected Salary Fund Balance</p><h2>BDT {{ number_format($projectedSalaryFundBalance, 2) }}</h2></div>
                                    </div>
                                </div>
                            @endif
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
                @endif
            @empty
                <div class="card">{{ $isFinalSettlementQueue ? 'No final settlement work found.' : 'No payroll actions found.' }}</div>
            @endforelse
        </div>
    @endif
@endsection
