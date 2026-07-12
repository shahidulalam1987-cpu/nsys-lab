@extends('layouts.admin')

@section('content')
    @php
        $activeStatus = $filters['status'] ?? null;
        $exportQuery = http_build_query($filters ?? []);
        $statusLabels = ['upcoming' => 'Upcoming', 'unpaid' => 'Unpaid', 'partial' => 'Partially Paid', 'paid' => 'Paid'];
    @endphp

    <h1>Salary Generate</h1>

    <p>
        <a class="btn" href="/admin/payroll/create">Generate Salary</a>
        <a class="btn" href="/admin/payroll/payment-report">Salary Payment Report</a>
        <a class="btn" href="/admin/payroll/export/csv?{{ $exportQuery }}">Export CSV</a>
        <a class="btn" href="/admin/payroll/export/excel?{{ $exportQuery }}">Export Excel</a>
    </p>

    <p>Generate salary based on employee working days for the selected month.</p>

    <div class="card" style="margin-top:20px;">
        <form method="GET" action="/admin/payroll">
            <input type="month" name="month" value="{{ request('month') }}">

            <select name="employee_id">
                <option value="">All Employees</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                        {{ $employee->name }} ({{ $employee->employee_id }})
                    </option>
                @endforeach
            </select>

            <select name="status">
                <option value="">All Status</option>
                @foreach(['upcoming' => 'Upcoming', 'unpaid' => 'Unpaid', 'partial' => 'Partially Paid', 'paid' => 'Paid', 'due' => 'Unpaid / Due'] as $value => $label)
                    <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <select name="salary_source">
                <option value="">All Salary Sources</option>
                @foreach(\App\Models\Employee::SALARY_SOURCES as $value => $label)
                    <option value="{{ $value }}" {{ request('salary_source') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            @if(request('status') === 'due')
                <select name="employee_scope">
                    <option value="all" {{ request('employee_scope', 'all') === 'all' ? 'selected' : '' }}>All</option>
                    <option value="active" {{ request('employee_scope') === 'active' ? 'selected' : '' }}>Active Employees</option>
                    <option value="terminated" {{ request('employee_scope') === 'terminated' ? 'selected' : '' }}>Terminated Final Settlement</option>
                </select>
            @endif

            <button class="btn" type="submit">Filter</button>
            <a href="/admin/payroll">Reset</a>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <p>Total Payroll Generated</p>
            <h2>BDT {{ number_format($summary['total_payable'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Total Payroll Paid</p>
            <h2>BDT {{ number_format($summary['total_paid'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Current Payroll Due</p>
            <h2>BDT {{ number_format($summary['total_due'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Upcoming This Week</p>
            <h2>{{ number_format($summary['upcoming_count'] ?? 0) }}</h2>
        </div>
        <div class="stat-card">
            <p>Overdue Payroll</p>
            <h2>{{ number_format($summary['overdue_count'] ?? 0) }}</h2>
        </div>
        <div class="stat-card">
            <p>Final Settlement Due</p>
            <h2>{{ number_format($summary['final_settlement_count'] ?? 0) }}</h2>
            <p>BDT {{ number_format($summary['final_settlement_amount'] ?? 0, 2) }}</p>
        </div>
    </div>

    <div class="card">
        <h2>Current Month Summary</h2>
        <p>
            Generated: <strong>BDT {{ number_format($summary['current_month_payable'] ?? 0, 2) }}</strong>
            &nbsp; | &nbsp;
            Paid: <strong>BDT {{ number_format($summary['current_month_paid'] ?? 0, 2) }}</strong>
            &nbsp; | &nbsp;
            Due: <strong>BDT {{ number_format($summary['current_month_due'] ?? 0, 2) }}</strong>
            &nbsp; | &nbsp;
            Records: <strong>{{ number_format($summary['record_count'] ?? $payrolls->count()) }}</strong>
        </p>
    </div>

    @if($activeStatus === 'due' && $payrolls->isNotEmpty())
        <div class="card" style="border-color:#ef4444;">
            <h2>Unpaid Salary Due</h2>
            <p>Generated salary records that are not fully paid.</p>
        </div>
    @elseif($activeStatus === 'upcoming' && ($cycleEmployees ?? collect())->isNotEmpty())
        <div class="card" style="border-color:#f59e0b;">
            <h2>Upcoming Salary This Week</h2>
            <p>Confirmed employees whose salary date is within the next 5 days.</p>
        </div>
    @endif

    @if($activeStatus === 'paid')
        <div class="card">
            <h2>Paid Salary History</h2>
            <p>Confirmed salary transfers with finance account and transaction reference.</p>
        </div>
    @endif

    <div class="card">
        <table>
            @if($activeStatus === 'upcoming')
                <tr>
                    <th>Employee</th>
                    <th>Client</th>
                    <th>Salary Amount</th>
                    <th>Salary Date</th>
                    <th>Days Remaining</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            @elseif($activeStatus === 'paid')
                <tr>
                    <th>Employee</th>
                    <th>Month</th>
                    <th>Client</th>
                    <th>Salary</th>
                    <th>Payment Date</th>
                    <th>Bank Name</th>
                    <th>Account Number</th>
                    <th>Finance Account</th>
                    <th>Transaction Reference</th>
                    <th>Status</th>
                    <th>Proof</th>
                    <th>Action</th>
                </tr>
            @elseif($activeStatus === 'due')
                <tr>
                    <th>Employee</th>
                    <th>Client</th>
                    <th>Amount Due</th>
                    <th>Salary Date</th>
                    <th>Overdue</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            @else
                <tr>
                    <th>Month / Period</th>
                    <th>Employee</th>
                    <th>Client</th>
                    <th>Salary Source</th>
                    <th>Working Days</th>
                    <th>Payable Salary (BDT)</th>
                    <th>Paid Salary</th>
                    <th>Remaining Due</th>
                    <th>Payment Status</th>
                    <th>Payroll Status</th>
                    <th>Generation</th>
                    <th>Payment Date</th>
                    <th>Proof</th>
                    <th>Action</th>
                </tr>
            @endif
            @forelse($payrolls as $payroll)
                @php
                    $salaryDate = $payroll->salaryDueDate();
                    $remainingDue = max($payroll->payable_salary - $payroll->paid_amount, 0);
                @endphp
                <tr>
                    @if($activeStatus === 'upcoming')
                        <td><a href="/admin/employees/{{ $payroll->employee?->id }}">{{ $payroll->employee?->name ?: '-' }}</a></td>
                        <td>{{ $payroll->client?->company_name ?: '-' }}</td>
                        <td>BDT {{ number_format($remainingDue, 2) }}</td>
                        <td>{{ $salaryDate?->toDateString() ?: '-' }}</td>
                        <td>{{ $payroll->daysUntilDue() ?? '-' }}</td>
                        <td>{{ $statusLabels[$payroll->calculated_status] ?? ucfirst($payroll->calculated_status) }}</td>
                    @elseif($activeStatus === 'paid')
                        <td><a href="/admin/employees/{{ $payroll->employee?->id }}">{{ $payroll->employee?->name ?: '-' }}</a></td>
                        <td>{{ $payroll->salary_month?->format('Y-m') ?: '-' }}</td>
                        <td>{{ $payroll->client?->company_name ?: '-' }}</td>
                        <td>BDT {{ number_format($payroll->paid_amount, 2) }}</td>
                        <td>{{ $payroll->payment_date?->toDateString() ?: '-' }}</td>
                        <td>{{ $payroll->snapshotBankName() }}</td>
                        <td>{{ $payroll->snapshotAccountNumber() }}</td>
                        <td>{{ $payroll->finance_account_name ?: ($payroll->financeAccount?->account_name ?: '-') }}</td>
                        <td>{{ $payroll->transaction_id ?: '-' }}</td>
                        <td><span class="badge {{ $payroll->payrollStatusBadgeClass() }}">{{ $payroll->payrollStatusLabel() }}</span></td>
                        <td>
                            @if($payroll->salary_payment_attachment)
                                <a href="/storage/{{ $payroll->salary_payment_attachment }}" target="_blank">View Proof</a>
                            @elseif($payroll->payment_proof)
                                <a href="/storage/{{ $payroll->payment_proof }}" target="_blank">View Proof</a>
                            @else
                                -
                            @endif
                        </td>
                    @elseif($activeStatus === 'due')
                        <td>
                            <a href="/admin/employees/{{ $payroll->employee?->id }}">{{ $payroll->employee?->name ?: '-' }}</a>
                            @if($payroll->isFinalSettlementPayroll())
                                <br><span style="color:var(--muted);">Last Working: {{ $payroll->employee?->last_working_date?->toDateString() ?: '-' }}</span>
                                <br><span style="color:var(--muted);">Period: {{ $payroll->salary_period }}</span>
                                <br><span style="color:var(--muted);">Working: {{ number_format((float) $payroll->working_days, 2) }} | Non Working: {{ number_format((float) $payroll->non_working_days, 2) }}</span>
                                <br><span style="color:var(--muted);">Payable: BDT {{ number_format((float) $payroll->payable_salary, 2) }}</span>
                            @endif
                        </td>
                        <td>{{ $payroll->client?->company_name ?: '-' }}</td>
                        <td>BDT {{ number_format($remainingDue, 2) }}</td>
                        <td>{{ $payroll->salaryDueDate()?->toDateString() ?: '-' }}</td>
                        <td>{{ $payroll->overdueLabel() }}</td>
                        <td>{{ $payroll->settlementStatusLabel() }}</td>
                    @else
                        <td>{{ $payroll->salary_period }}</td>
                        <td>
                            @if($payroll->employee)
                                <a href="/admin/employees/{{ $payroll->employee->id }}">{{ $payroll->employee->name }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $payroll->client?->company_name ?: '-' }}</td>
                        <td>{{ $payroll->salarySourceLabel() }}</td>
                        <td>{{ $payroll->working_days ?? '-' }}</td>
                        <td>BDT {{ number_format($payroll->payable_salary, 2) }}</td>
                        <td>BDT {{ number_format($payroll->paid_amount, 2) }}</td>
                        <td>BDT {{ number_format($remainingDue, 2) }}</td>
                        <td>{{ $statusLabels[$payroll->calculated_status] ?? ucfirst($payroll->calculated_status) }}</td>
                        <td><span class="badge {{ $payroll->payrollStatusBadgeClass() }}">{{ $payroll->payrollStatusLabel() }}</span></td>
                        <td><span class="badge {{ $payroll->generationStatusBadgeClass() }}">{{ $payroll->generationStatusLabel() }}</span></td>
                        <td>{{ $payroll->payment_date?->toDateString() ?: '-' }}</td>
                        <td>
                            @if($payroll->payment_proof)
                                <a href="/storage/{{ $payroll->payment_proof }}" target="_blank">View Proof</a>
                            @else
                                -
                            @endif
                        </td>
                    @endif
                    <td>
                        @if($payroll->canMarkPaid() && $payroll->payroll_status !== 'paid' && auth()->user()->hasPermission('payroll.pay'))
                            <button class="btn" type="button" onclick="document.getElementById('confirm-payment-{{ $payroll->id }}').style.display='flex';">Confirm Payment</button>
                            |
                        @endif
                        <a href="/admin/payroll/{{ $payroll->id }}">View</a>
                        |
                        <a href="/admin/payroll/{{ $payroll->id }}/edit">Edit</a>
                        |
                        <form method="POST" action="/admin/payroll/{{ $payroll->id }}/delete" style="display:inline;">
                            @csrf
                            <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this salary record?');">Delete</button>
                        </form>
                    </td>
                </tr>
                @if($payroll->canMarkPaid() && $payroll->payroll_status !== 'paid' && auth()->user()->hasPermission('payroll.pay'))
                    <tr>
                        <td colspan="14" style="padding:0;border:0;">
                            <div id="confirm-payment-{{ $payroll->id }}" style="display:none;position:fixed;inset:0;background:rgba(2,6,23,.74);z-index:100;align-items:center;justify-content:center;padding:20px;">
                                <div class="card" style="max-width:760px;width:100%;max-height:90vh;overflow:auto;">
                                    <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
                                        <div>
                                            <h2>Confirm Payment</h2>
                                            <p>{{ $payroll->snapshotEmployeeName() }} | {{ $payroll->snapshotEmployeeCode() }}</p>
                                        </div>
                                        <button type="button" class="btn" onclick="document.getElementById('confirm-payment-{{ $payroll->id }}').style.display='none';">Close</button>
                                    </div>
                                    <div class="stats-grid">
                                        <div class="stat-card"><p>Salary Month</p><h2>{{ $payroll->salary_month?->format('Y-m') ?: '-' }}</h2></div>
                                        <div class="stat-card"><p>Client</p><h2>{{ $payroll->client?->company_name ?: '-' }}</h2></div>
                                        <div class="stat-card"><p>Payable</p><h2>BDT {{ number_format($payroll->payable_salary, 2) }}</h2></div>
                                        <div class="stat-card"><p>Bank</p><h2>{{ $payroll->snapshotBankName() }}</h2><p>{{ $payroll->snapshotAccountNumber() }}</p></div>
                                    </div>
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
                                            <label>Payment Date<br><input type="date" name="payment_date" value="{{ now()->toDateString() }}" required></label>
                                            <label>From Finance Account<br>
                                                <select name="finance_account_id" required>
                                                    <option value="">Select Account</option>
                                                    @foreach($financeAccounts as $account)
                                                        <option value="{{ $account->id }}">{{ $account->account_name }} - {{ $account->currency }} {{ number_format((float) $account->current_balance, 2) }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <label>Transaction Reference<br><input type="text" name="transaction_id" required></label>
                                            <label>Attachment / Screenshot<br><input type="file" name="salary_payment_attachment" accept="image/*"></label>
                                            <label style="grid-column:1 / -1;">Payment Note<br><textarea name="payment_note" required>Salary payment for {{ $payroll->salary_month?->format('F Y') }}</textarea></label>
                                        </div>
                                        <div style="display:flex;justify-content:flex-end;margin-top:12px;">
                                            <button class="btn" type="submit">Confirm Payment</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="14">No salary records found.</td></tr>
            @endforelse
        </table>
    </div>

    @php
        $cycleGroups = match($activeStatus) {
            'upcoming' => [
                'Upcoming Salary' => ($cycleEmployees ?? collect())->where('cycle_category', 'upcoming'),
            ],
            'due' => [
                'Salary Ready' => ($cycleEmployees ?? collect())->where('cycle_category', 'salary_ready'),
                'Pending Work Status' => ($cycleEmployees ?? collect())->where('cycle_category', 'pending_work_status'),
                'Final Settlement Pending' => ($cycleEmployees ?? collect())->where('cycle_category', 'final_settlement_pending'),
            ],
            default => [
                'Upcoming Salary' => ($cycleEmployees ?? collect())->where('cycle_category', 'upcoming'),
                'Salary Ready' => ($cycleEmployees ?? collect())->where('cycle_category', 'salary_ready'),
                'Pending Work Status' => ($cycleEmployees ?? collect())->where('cycle_category', 'pending_work_status'),
                'Final Settlement Pending' => ($cycleEmployees ?? collect())->where('cycle_category', 'final_settlement_pending'),
            ],
        };
    @endphp

    @foreach($cycleGroups as $cycleGroupTitle => $cycleGroupEmployees)
    @if($cycleGroupEmployees->isNotEmpty())
        <div class="card">
            <h2>{{ $cycleGroupTitle }}</h2>
            <p>Confirmed employees without a generated salary record for this cycle.</p>

            <table>
                <tr>
                    <th>Employee</th>
                    <th>Client</th>
                    <th>Salary Day</th>
                        <th>Salary Date</th>
                        <th>Estimated Amount Due</th>
                        <th>Estimate Status</th>
                    <th>Action</th>
                </tr>
                @foreach($cycleGroupEmployees as $cycleEmployee)
                    @php
                        $cycleSalaryDate = $cycleEmployee->cycle_salary_date
                            ?: ($cycleEmployee->status === 'terminated'
                                ? $cycleEmployee->last_working_date
                                : (request('status') === 'due'
                                    ? $cycleEmployee->currentSalaryDueDate()
                                    : $cycleEmployee->nextSalaryDate()));
                        $cycleEstimate = $cycleEmployee->cycle_estimate ?? [];
                        $estimatedAmount = (float) data_get($cycleEstimate, 'estimated_payable_salary', 0);
                        $workStatusMissing = data_get($cycleEstimate, 'estimate_status') === 'work_status_missing';
                        $canGenerateCycleSalary = ! ($estimatedAmount <= 0 && $workStatusMissing);
                        $eligibilityLabel = data_get($cycleEstimate, 'eligibility_label', $workStatusMissing ? 'Pending Work Status' : 'Salary Ready');
                        $activeAssignment = $cycleEmployee->activeAssignments->first();
                        $workStatusQuery = [
                            'entry_mode' => 'monthly',
                            'employee_id' => $cycleEmployee->id,
                            'salary_month' => data_get($cycleEstimate, 'salary_period_end')?->format('Y-m'),
                            'status' => 'working',
                            'note' => 'Salary cycle work status entry',
                            'return_to' => '/admin/payroll?status=due',
                        ];
                        if (! $cycleEmployee->isAgencyInternal() && $activeAssignment?->client_id) {
                            $workStatusQuery['client_id'] = $activeAssignment->client_id;
                        }
                        $addWorkStatusUrl = '/admin/work-status/create?' . http_build_query(array_filter(
                            $workStatusQuery,
                            fn ($value) => $value !== null && $value !== ''
                        ));
                        $generateSalaryQuery = [
                            'employee_id' => $cycleEmployee->id,
                            'client_id' => $activeAssignment?->client_id,
                            'salary_date' => $cycleSalaryDate?->toDateString(),
                            'cycle_start' => data_get($cycleEstimate, 'salary_period_start')?->toDateString(),
                            'cycle_end' => data_get($cycleEstimate, 'salary_period_end')?->toDateString(),
                            'calculation_type' => 'date_to_date',
                            'use_work_status' => 1,
                        ];
                        $generateSalaryUrl = '/admin/payroll/create?' . http_build_query(array_filter(
                            $generateSalaryQuery,
                            fn ($value) => $value !== null && $value !== ''
                        ));
                    @endphp
                    <tr>
                        <td>
                            <a href="/admin/employees/{{ $cycleEmployee->id }}">{{ $cycleEmployee->employee_id }}</a><br>
                            {{ $cycleEmployee->name }}
                            @if($cycleEmployee->status === 'terminated')
                                <br><span style="color:var(--muted);">Last Working Date: {{ $cycleEmployee->last_working_date?->toDateString() ?: '-' }}</span>
                                <br><span style="color:var(--muted);">Final salary not generated yet</span>
                            @endif
                        </td>
                        <td>{{ $cycleEmployee->activeAssignments->first()?->client?->company_name ?: '-' }}</td>
                        <td>{{ $cycleEmployee->salaryCycleDay() ?: '-' }}</td>
                        <td>{{ $cycleSalaryDate?->toDateString() ?: '-' }}</td>
                        <td>
                            BDT {{ number_format($estimatedAmount, 2) }}
                            <br><span style="color:var(--muted);">
                                Working: {{ number_format((float) data_get($cycleEstimate, 'working_salary_count', 0), 2) }}
                                | Payable Count: {{ number_format((float) data_get($cycleEstimate, 'effective_salary_count', 0), 2) }}
                                | Non Working: {{ number_format((float) data_get($cycleEstimate, 'non_working_count', 0), 2) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $canGenerateCycleSalary ? 'badge-success' : 'badge-warning' }}">{{ $eligibilityLabel }}</span>
                            <br><span style="color:var(--muted);">{{ $workStatusMissing ? 'Pending Work Status' : data_get($cycleEstimate, 'estimate_status_label', 'Based on Work Status') }}</span>
                            @if($cycleEmployee->status === 'terminated')
                                <br><span style="color:var(--muted);">Final Salary Pending</span>
                            @endif
                        </td>
                        <td>
                            @if($canGenerateCycleSalary)
                                <a class="btn" href="{{ $generateSalaryUrl }}">{{ $cycleEmployee->status === 'terminated' ? 'Generate Final Salary' : 'Generate Salary' }}</a>
                            @else
                                <a class="btn" href="{{ $addWorkStatusUrl }}">Add Work Status</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif
    @endforeach
@endsection
