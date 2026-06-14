<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Official Salary Statement - {{ $reference }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.45;
            margin: 0;
        }
        .page {
            padding: 28px;
        }
        .header {
            border-bottom: 2px solid #111827;
            margin-bottom: 18px;
            padding-bottom: 14px;
        }
        .brand {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: .5px;
            margin: 0;
        }
        .subtitle {
            color: #374151;
            font-size: 14px;
            font-weight: 700;
            margin: 3px 0 0;
            text-transform: uppercase;
        }
        .meta {
            margin-top: 10px;
            width: 100%;
        }
        .meta td {
            padding: 2px 0;
            vertical-align: top;
            width: 25%;
        }
        .section {
            border: 1px solid #d1d5db;
            margin-bottom: 12px;
            padding: 10px;
        }
        .section-title {
            background: #111827;
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
            margin: -10px -10px 10px;
            padding: 7px 10px;
            text-transform: uppercase;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f3f4f6;
            color: #111827;
            font-weight: 700;
        }
        .plain td {
            border: 0;
            padding: 3px 6px 3px 0;
        }
        .label {
            color: #4b5563;
            font-weight: 700;
            white-space: nowrap;
        }
        .amount {
            font-weight: 700;
            text-align: right;
            white-space: nowrap;
        }
        .formula {
            background: #f9fafb;
            border: 1px solid #d1d5db;
            padding: 10px;
        }
        .formula p {
            margin: 2px 0;
        }
        .badge {
            border: 1px solid #9ca3af;
            border-radius: 10px;
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
        }
        .two-col {
            width: 100%;
        }
        .two-col > tbody > tr > td {
            border: 0;
            padding: 0 6px 0 0;
            width: 50%;
        }
        .footer {
            border-top: 1px solid #d1d5db;
            color: #4b5563;
            font-size: 10px;
            margin-top: 18px;
            padding-top: 8px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <h1 class="brand">NSYS Agency</h1>
        <p class="subtitle">Official Salary Statement</p>
        <table class="meta">
            <tr>
                <td><span class="label">Payroll Reference:</span><br>{{ $reference }}</td>
                <td><span class="label">Generated Date:</span><br>{{ now()->format('Y-m-d') }}</td>
                <td><span class="label">Salary Month:</span><br>{{ $payroll->salary_month?->format('F Y') ?: '-' }}</td>
                <td><span class="label">Salary Period:</span><br>{{ $payroll->salary_period }}</td>
            </tr>
        </table>
    </div>

    <table class="two-col">
        <tr>
            <td>
                <div class="section">
                    <div class="section-title">Employee Information</div>
                    <table class="plain">
                        <tr><td class="label">Employee Name</td><td>{{ $employee?->name ?: $payroll->snapshotEmployeeName() }}</td></tr>
                        <tr><td class="label">Employee ID</td><td>{{ $employee?->employee_id ?: $payroll->snapshotEmployeeCode() }}</td></tr>
                        <tr><td class="label">Department</td><td>{{ $employee?->department ?: '-' }}</td></tr>
                        <tr><td class="label">Role</td><td>{{ $employee?->role ?: '-' }}</td></tr>
                        <tr><td class="label">Employment Type</td><td>{{ $employee?->employeeTypeLabel() ?: '-' }}</td></tr>
                        <tr><td class="label">Joining Date</td><td>{{ $employee?->joining_date?->toDateString() ?: '-' }}</td></tr>
                        <tr><td class="label">Confirmation Date</td><td>{{ $employee?->confirmation_date?->toDateString() ?: '-' }}</td></tr>
                        <tr><td class="label">Current Status</td><td>{{ $employee?->statusLabel() ?: '-' }}</td></tr>
                        @if($employee?->status === 'terminated')
                            <tr><td class="label">Last Working Date</td><td>{{ $employee->last_working_date?->toDateString() ?: '-' }}</td></tr>
                            <tr><td class="label">Termination Reason</td><td>-</td></tr>
                        @endif
                    </table>
                </div>
            </td>
            <td>
                <div class="section">
                    <div class="section-title">Client & Salary Source</div>
                    <table class="plain">
                        <tr><td class="label">Client</td><td>{{ $payroll->client?->company_name ?: '-' }}</td></tr>
                        <tr><td class="label">Salary Source</td><td>{{ $payroll->salarySourceLabel() }}</td></tr>
                        <tr><td class="label">Calculation Type</td><td>{{ $payroll->calculationTypeLabel() }}</td></tr>
                        <tr><td class="label">Payroll Status</td><td><span class="badge">{{ $payroll->payrollStatusLabel() }}</span></td></tr>
                        <tr><td class="label">Payment Status</td><td>{{ $payroll->settlementStatusLabel() }}</td></tr>
                        <tr><td class="label">Salary Policy</td><td>Fixed 30 Days</td></tr>
                        <tr><td class="label">Verify Code</td><td>{{ $reference }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Salary Summary</div>
        <table>
            <tr>
                <th>Monthly Salary</th>
                <th>Month Days</th>
                <th>Working Days</th>
                <th>Half Days</th>
                <th>Leave Days</th>
                <th>Client Issue</th>
                <th>Boosting OFF</th>
                <th>Non Working</th>
            </tr>
            <tr>
                <td>BDT {{ number_format($monthlySalary, 2) }}</td>
                <td>{{ $monthDays }}</td>
                <td>{{ number_format($summary['working_days'], 2) }}</td>
                <td>{{ number_format($summary['half_days']) }}</td>
                <td>{{ number_format($summary['leave_days']) }}</td>
                <td>{{ number_format($summary['client_issue_days']) }}</td>
                <td>{{ number_format($summary['boosting_off_days']) }}</td>
                <td>{{ number_format($summary['non_working_days'], 2) }}</td>
            </tr>
            <tr>
                <th colspan="2">Daily Salary</th>
                <th colspan="3">Final Payable Salary</th>
                <th colspan="3">Remaining Due</th>
            </tr>
            <tr>
                <td colspan="2">BDT {{ number_format($dailySalary, 2) }}</td>
                <td colspan="3">BDT {{ number_format((float) $payroll->payable_salary, 2) }}</td>
                <td colspan="3">BDT {{ number_format($remainingDue, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Salary Calculation Formula</div>
        <div class="formula">
            <p><strong>Salary Policy:</strong> Fixed 30 Days</p>
            <p><strong>Daily Salary:</strong> Monthly Salary / 30 = BDT {{ number_format($monthlySalary, 2) }} / {{ $monthDays }} = BDT {{ number_format($dailySalary, 2) }}</p>
            <p><strong>Payable Salary:</strong> Daily Salary x Working Day Value = BDT {{ number_format($dailySalary, 2) }} x {{ number_format($workingDays, 2) }} = BDT {{ number_format($finalSalaryFormula, 2) }}</p>
            <p><strong>Final Payable Salary:</strong> BDT {{ number_format((float) $payroll->payable_salary, 2) }}</p>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Daily Work Status Breakdown</div>
        <table>
            <tr>
                <th>Date</th>
                <th>Day Type</th>
                <th>Salary Count</th>
                <th>Reason</th>
                <th>Note</th>
            </tr>
            @forelse($adjustments as $adjustment)
                @php
                    $salaryCount = (float) ($adjustment['salary_count_value'] ?? 0);
                    $reason = $adjustment['reason'] ?? $adjustment['status'] ?? 'active_working';
                    $dayType = $salaryCount === 0.5 ? 'Half Day' : ($salaryCount > 0 ? 'Working' : 'Non Working');
                @endphp
                <tr>
                    <td>{{ $adjustment['date'] ?? '-' }}</td>
                    <td>{{ $dayType }}</td>
                    <td>{{ number_format($salaryCount, 2) }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $reason)) }}</td>
                    <td>{{ $adjustment['note'] ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No date-wise work status breakdown was saved for this payroll.</td></tr>
            @endforelse
        </table>
    </div>

    @if($employee?->status === 'terminated')
        <div class="section">
            <div class="section-title">Final Settlement</div>
            <table>
                <tr>
                    <th>Payable Salary</th>
                    <th>Paid Salary</th>
                    <th>Remaining Due</th>
                    <th>Last Working Date</th>
                    <th>Final Settlement Status</th>
                </tr>
                <tr>
                    <td>BDT {{ number_format((float) $payroll->payable_salary, 2) }}</td>
                    <td>BDT {{ number_format((float) $payroll->paid_amount, 2) }}</td>
                    <td>BDT {{ number_format($remainingDue, 2) }}</td>
                    <td>{{ $employee->last_working_date?->toDateString() ?: '-' }}</td>
                    <td>{{ $payroll->settlementStatusLabel() }}</td>
                </tr>
            </table>
        </div>
    @endif

    <div class="section">
        <div class="section-title">Payment Information</div>
        @if((float) $payroll->paid_amount > 0 || $payroll->payroll_status === 'paid')
            <table class="plain">
                <tr><td class="label">Payment Date</td><td>{{ $payroll->payment_date?->toDateString() ?: $payroll->paid_at?->format('Y-m-d') ?: '-' }}</td></tr>
                <tr><td class="label">Payment Method</td><td>{{ $payroll->payment_method ?: '-' }}</td></tr>
                <tr><td class="label">Finance Account</td><td>{{ $payroll->finance_account_name ?: ($payroll->financeAccount?->account_name ?: '-') }}</td></tr>
                <tr><td class="label">Transaction ID / Reference</td><td>{{ $payroll->transaction_id ?: '-' }}</td></tr>
                <tr><td class="label">Paid Amount</td><td>BDT {{ number_format((float) $payroll->paid_amount, 2) }}</td></tr>
                <tr><td class="label">Bank Name</td><td>{{ $payroll->snapshotBankName() }}</td></tr>
                <tr><td class="label">Account Name</td><td>{{ $payroll->snapshotAccountName() }}</td></tr>
                <tr><td class="label">Account Number</td><td>{{ $payroll->snapshotAccountNumber() }}</td></tr>
                <tr><td class="label">Branch Name</td><td>{{ $payroll->snapshotBranchName() }}</td></tr>
            </table>
        @else
            <p>No salary payment has been recorded for this payroll yet.</p>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Company Approval</div>
        <table>
            <tr>
                <th>Generated By</th>
                <th>Approved By</th>
                <th>Approved Date</th>
                <th>Reference / Verification Code</th>
            </tr>
            <tr>
                <td>NSYS Agency Payroll System</td>
                <td>{{ $payroll->approver?->name ?: '-' }}</td>
                <td>{{ $payroll->approved_at?->format('Y-m-d H:i') ?: '-' }}</td>
                <td>{{ $reference }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        This official salary statement was generated by NSYS Agency Payroll System. Reference: {{ $reference }}.
    </div>
</div>
</body>
</html>
