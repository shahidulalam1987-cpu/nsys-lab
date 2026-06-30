@extends('layouts.admin')

@section('content')
    @php
        $paymentStatusLabels = ['upcoming' => 'Upcoming', 'unpaid' => 'Unpaid', 'partial' => 'Partially Paid', 'paid' => 'Paid'];
        $remainingDue = max($payroll->payable_salary - $payroll->paid_amount, 0);
    @endphp

    <h1>Salary Details</h1>

    <style>
        .payroll-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .payroll-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
        }

        .payroll-actions form {
            display: inline;
            margin: 0;
        }

        .info-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(160px, 1fr));
            gap: 10px 18px;
        }

        .info-list p {
            margin: 0;
        }

        @media (max-width: 900px) {
            .payroll-detail-grid,
            .info-list {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="payroll-actions">
        <a class="btn" href="/admin/payroll?status=due">Back to Unpaid Salary</a>
        <a class="btn" href="/admin/payroll/{{ $payroll->id }}/edit">Edit Salary</a>
        <a class="btn" href="/admin/payroll/{{ $payroll->id }}/salary-statement">Download Salary PDF</a>

        @if($payroll->canApprove())
            <form method="POST" action="/admin/payroll/{{ $payroll->id }}/approve">
                @csrf
                <button class="btn" type="submit">Approve Payroll</button>
            </form>
        @endif

        @if($payroll->canMarkPaid() && $payroll->payroll_status !== 'paid')
            <button class="btn btn-success" type="button" onclick="document.getElementById('confirm-payment-panel').style.display='block';">Confirm Payment</button>
        @endif
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <p>Payroll Status</p>
            <h2><span class="badge {{ $payroll->payrollStatusBadgeClass() }}">{{ $payroll->payrollStatusLabel() }}</span></h2>
        </div>
        <div class="stat-card">
            <p>Generation</p>
            <h2><span class="badge {{ $payroll->generationStatusBadgeClass() }}">{{ $payroll->generationStatusLabel() }}</span></h2>
        </div>
        <div class="stat-card"><p>Payable Salary</p><h2>BDT {{ number_format($payroll->payable_salary, 2) }}</h2></div>
        <div class="stat-card"><p>Remaining Due</p><h2>BDT {{ number_format($remainingDue, 2) }}</h2></div>
    </div>

    @if($payroll->canMarkPaid() && $payroll->payroll_status !== 'paid')
        <div class="card" id="confirm-payment-panel" style="display:none;border-color:#22c55e;">
            <h2>Confirm Payment</h2>
            <p>Record finance account, transaction reference, and salary transfer note before moving this salary to Salary Report.</p>
            <div class="stats-grid">
                <div class="stat-card"><p>Employee</p><h2>{{ $payroll->snapshotEmployeeName() }}</h2><p>{{ $payroll->snapshotEmployeeCode() }}</p></div>
                <div class="stat-card"><p>Salary Month</p><h2>{{ $payroll->salary_month?->format('Y-m') ?: '-' }}</h2></div>
                <div class="stat-card"><p>Client</p><h2>{{ $payroll->client?->company_name ?: '-' }}</h2></div>
                <div class="stat-card"><p>Payable Amount</p><h2>BDT {{ number_format($payroll->payable_salary, 2) }}</h2></div>
            </div>
            <div class="card" style="background:rgba(255,255,255,.05);">
                <h3>Bank Information Snapshot</h3>
                <p><strong>Bank Name:</strong> {{ $payroll->snapshotBankName() }}</p>
                <p><strong>Account Name:</strong> {{ $payroll->snapshotAccountName() }}</p>
                <p><strong>Account Number:</strong> {{ $payroll->snapshotAccountNumber() }}</p>
                <p><strong>Branch:</strong> {{ $payroll->snapshotBranchName() }}</p>
            </div>
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
                    <button class="btn btn-success" type="submit">Confirm Payment</button>
                </div>
            </form>
        </div>
    @endif

    <div class="payroll-detail-grid">
        <div class="card" style="margin-top:0;">
            <h2>Employee Information</h2>
            <div class="info-list">
                <p><strong>Name:</strong> {{ $payroll->employee?->name ?: '-' }}</p>
                <p><strong>Employee ID:</strong> {{ $payroll->employee?->employee_id ?: '-' }}</p>
                <p><strong>Employment Type:</strong> {{ $payroll->employee?->employeeTypeLabel() ?: '-' }}</p>
                <p><strong>Department:</strong> {{ $payroll->employee?->departmentName() ?: '-' }}</p>
                <p><strong>Role:</strong> {{ $payroll->employee?->roleName() ?: '-' }}</p>
                <p><strong>Salary Source:</strong> {{ $payroll->salarySourceLabel() }}</p>
                <p><strong>Salary Day:</strong> {{ $payroll->employee?->salaryCycleDay() ?: '-' }}</p>
                <p><strong>Salary Date:</strong> {{ $payroll->employee?->salaryDateForMonth($payroll->salary_month?->copy() ?: now())?->toDateString() ?: '-' }}</p>
            </div>
        </div>

        <div class="card" style="margin-top:0;">
            <h2>Client Information</h2>
            <div class="info-list">
                <p><strong>Client:</strong> {{ $payroll->client?->company_name ?: '-' }}</p>
                <p><strong>Calculation Type:</strong> {{ $payroll->calculationTypeLabel() }}</p>
                <p><strong>Salary Month:</strong> {{ $payroll->salary_month?->format('Y-m') ?: '-' }}</p>
                <p><strong>Salary Period:</strong> {{ $payroll->salary_period }}</p>
                <p><strong>Generated Date:</strong> {{ $payroll->created_at?->toDateString() ?: '-' }}</p>
                <p><strong>Regenerated From:</strong> {{ $payroll->regenerated_from_id ? '#' . $payroll->regenerated_from_id : '-' }}</p>
            </div>
        </div>
    </div>

    <div class="payroll-detail-grid">
        <div class="card">
            <h2>Work Status Summary</h2>
            <div class="info-list">
                <p><strong>Working Days:</strong> {{ number_format($workStatusSummary['working_days'], 2) }}</p>
                <p><strong>Half Days:</strong> {{ number_format($workStatusSummary['half_days']) }}</p>
                <p><strong>Leave:</strong> {{ number_format($workStatusSummary['leave']) }}</p>
                <p><strong>Client Issue:</strong> {{ number_format($workStatusSummary['client_issue']) }}</p>
                <p><strong>Boosting OFF:</strong> {{ number_format($workStatusSummary['boosting_off']) }}</p>
            </div>
        </div>

        <div class="card">
            <h2>Salary Calculation Breakdown</h2>
            <div class="info-list">
                <p><strong>Working Days:</strong> {{ $payroll->working_days ?? '-' }}</p>
                <p><strong>Non Working Days:</strong> {{ $payroll->non_working_days ?? '-' }}</p>
                <p><strong>Monthly Salary:</strong> BDT {{ number_format($payroll->employee?->monthly_salary ?? 0, 2) }}</p>
                <p><strong>Month Days:</strong> {{ $payroll->month_days ?? '-' }}</p>
                <p><strong>Daily Salary:</strong> {{ $payroll->daily_salary !== null ? 'BDT ' . number_format($payroll->daily_salary, 2) : '-' }}</p>
                <p><strong>Payable Salary (BDT):</strong> BDT {{ number_format($payroll->payable_salary, 2) }}</p>
            </div>
        </div>
    </div>

    <div class="payroll-detail-grid">
        <div class="card">
            <h2>Approval History</h2>
            <div class="info-list">
                <p><strong>Approved By:</strong> {{ $payroll->approver?->name ?: '-' }}</p>
                <p><strong>Approved At:</strong> {{ $payroll->approved_at?->format('Y-m-d H:i') ?: '-' }}</p>
                <p><strong>Payroll Status:</strong> {{ $payroll->payrollStatusLabel() }}</p>
                <p><strong>Payment Status:</strong> {{ $paymentStatusLabels[$payroll->calculated_status] ?? ucfirst($payroll->calculated_status) }}</p>
            </div>
        </div>

        <div class="card">
            <h2>Payment History</h2>
            <div class="info-list">
                <p><strong>Paid Salary:</strong> BDT {{ number_format($payroll->paid_amount, 2) }}</p>
                <p><strong>Paid By:</strong> {{ $payroll->payer?->name ?: '-' }}</p>
                <p><strong>Bank Name:</strong> {{ $payroll->snapshotBankName() }}</p>
                <p><strong>Account Name:</strong> {{ $payroll->snapshotAccountName() }}</p>
                <p><strong>Account Number:</strong> {{ $payroll->snapshotAccountNumber() }}</p>
                <p><strong>Branch Name:</strong> {{ $payroll->snapshotBranchName() }}</p>
                <p><strong>Finance Account:</strong> {{ $payroll->finance_account_name ?: ($payroll->financeAccount?->account_name ?: '-') }}</p>
                <p><strong>Payment Method:</strong> {{ $payroll->payment_method ?: '-' }}</p>
                <p><strong>Payment Date:</strong> {{ $payroll->payment_date?->toDateString() ?: '-' }}</p>
                <p><strong>Transaction ID / Reference:</strong> {{ $payroll->transaction_id ?: '-' }}</p>
                <p><strong>Payment Note:</strong> {{ $payroll->payment_note ?: '-' }}</p>
                <p><strong>Payment Attachment:</strong>
                    @if($payroll->salary_payment_attachment)
                        <a href="/storage/{{ $payroll->salary_payment_attachment }}" target="_blank">View Attachment</a>
                    @else
                        -
                    @endif
                </p>
                <p><strong>Payment Proof:</strong>
                    @if($payroll->payment_proof)
                        <a href="/storage/{{ $payroll->payment_proof }}" target="_blank">View Proof</a>
                    @else
                        -
                    @endif
                </p>
            </div>
            @if($payroll->payroll_status === 'paid' && ! $payroll->reversed_at)
                <form method="POST" action="/admin/payroll/{{ $payroll->id }}/reverse-payment" onsubmit="return confirm('Reverse this salary payment and restore finance account balance?');">
                    @csrf
                    <p><strong>Reverse Salary Payment</strong></p>
                    <textarea name="reversal_note" placeholder="Reason for reversal" required></textarea>
                    <br>
                    <button class="btn btn-danger" type="submit">Reverse Payment</button>
                </form>
            @elseif($payroll->reversed_at)
                <p><strong>Reversed At:</strong> {{ $payroll->reversed_at?->format('Y-m-d H:i') }}</p>
                <p><strong>Reversal Note:</strong> {{ $payroll->reversal_note ?: '-' }}</p>
            @endif
        </div>
    </div>

    <div class="card">
        <h2>Finance Ledger</h2>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Account</th>
                    <th>Amount</th>
                    <th>Previous Balance</th>
                    <th>New Balance</th>
                    <th>Reference</th>
                </tr>
                @forelse($payroll->financeLedgers as $ledger)
                    <tr>
                        <td>{{ $ledger->ledger_date?->toDateString() }}</td>
                        <td>{{ $ledger->typeLabel() }}</td>
                        <td>{{ $ledger->account?->account_name ?: '-' }}</td>
                        <td>BDT {{ number_format((float) $ledger->amount, 2) }}</td>
                        <td>BDT {{ number_format((float) $ledger->previous_balance, 2) }}</td>
                        <td>BDT {{ number_format((float) $ledger->new_balance, 2) }}</td>
                        <td>{{ $ledger->reference ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">No finance ledger entries found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>

    <div class="card">
        <h2>Audit Log</h2>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Date Time</th>
                    <th>Admin Name</th>
                    <th>Action</th>
                    <th>Note</th>
                </tr>
                @forelse($payroll->audits as $audit)
                    <tr>
                        <td>{{ $audit->created_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $audit->user?->name ?: '-' }}</td>
                        <td>{{ $payroll->workflowActionLabel($audit->action) }}</td>
                        <td>{{ $audit->note ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No audit log found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>

    @if(! empty($payroll->salary_day_adjustments))
        <div class="card">
            <h2>Date-wise Adjustment</h2>
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Date</th>
                        <th>Day Type</th>
                        <th>Salary Count</th>
                        <th>Reason</th>
                        <th>Note</th>
                    </tr>
                    @foreach($payroll->salary_day_adjustments as $adjustment)
                        <tr>
                            <td>{{ $adjustment['date'] ?? '-' }}</td>
                            <td>{{ ($adjustment['day_type'] ?? 'working') === 'non_working' ? 'Non Working' : 'Working' }}</td>
                            <td>{{ number_format((float) ($adjustment['salary_count_value'] ?? 0), 2) }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $adjustment['reason'] ?? 'active_working')) }}</td>
                            <td>{{ $adjustment['note'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    @endif
@endsection
