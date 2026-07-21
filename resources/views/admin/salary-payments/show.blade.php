@extends('layouts.admin')

@section('content')
    @php
        $financeLedger = $payment->financeLedger();
        $clientFundLedger = $payment->clientFundLedger();
        $canViewSensitiveApproval = auth()->user()?->isSuperAdmin();
    @endphp

    <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <h1>Client Payment Details</h1>
            <p>Receipt {{ $payment->receiptNumber() }}</p>
        </div>
        <div>
            <a class="btn" href="/admin/salary-payments">Payment History</a>
            <a class="btn" href="/admin/salary-payments/{{ $payment->id }}/receipt-pdf">Download PDF</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Receipt Number</p><h2>{{ $payment->receiptNumber() }}</h2></div>
        <div class="stat-card"><p>Amount</p><h2>BDT {{ number_format($payment->amount, 2) }}</h2></div>
        <div class="stat-card"><p>Status</p><h2>{{ ucfirst($payment->status) }}</h2></div>
        <div class="stat-card"><p>Fund Type</p><h2>{{ $payment->fund_type === 'facebook_ads' ? 'Ads Fund' : 'Salary Fund' }}</h2></div>
    </div>

    <div class="stats-grid">
        <div class="card">
            <h2>Payment Information</h2>
            <p><strong>Client:</strong> {{ $payment->client?->company_name ?: '-' }}</p>
            <p><strong>Transaction ID:</strong> {{ $payment->transaction_id }}</p>
            <p><strong>Payment Method:</strong> {{ $payment->payment_method }}</p>
            <p><strong>Submitted At:</strong> {{ $payment->created_at?->format('Y-m-d H:i') }}</p>
            <p><strong>Submitted By:</strong> {{ $payment->client?->company_name ?: 'Client/Admin' }}</p>
        </div>

        <div class="card">
            <h2>Approval Information</h2>
            <p><strong>Approved By:</strong> {{ $payment->approver?->name ?: '-' }}</p>
            <p><strong>Approved At:</strong> {{ $payment->approved_at?->format('Y-m-d H:i') ?: '-' }}</p>
            @if($canViewSensitiveApproval)
                <p><strong>Approved From IP:</strong> {{ $payment->approved_ip ?: '-' }}</p>
                <p><strong>User Agent:</strong> {{ $payment->approved_user_agent ?: '-' }}</p>
            @endif
        </div>

        <div class="card">
            <h2>Finance Information</h2>
            <p><strong>Receive Into Finance Account:</strong> {{ $payment->financeAccount?->account_name ?: '-' }}</p>
            <p><strong>Finance Ledger ID:</strong> {{ $financeLedger?->id ?: '-' }}</p>
            <p><strong>Ledger Balance Before:</strong> {{ $financeLedger ? 'BDT ' . number_format((float) ($financeLedger->old_balance ?? $financeLedger->previous_balance), 2) : '-' }}</p>
            <p><strong>Ledger Balance After:</strong> {{ $financeLedger ? 'BDT ' . number_format((float) ($financeLedger->new_balance_snapshot ?? $financeLedger->new_balance), 2) : '-' }}</p>
        </div>

        <div class="card">
            <h2>Client Fund Information</h2>
            <p><strong>Client Fund Ledger ID:</strong> {{ $clientFundLedger?->id ?: '-' }}</p>
            <p><strong>Fund Type:</strong> {{ $payment->fund_type === 'facebook_ads' ? 'Facebook Ads Fund' : 'Employee Salary Fund' }}</p>
            <p><strong>Fund Balance Before:</strong> {{ $clientFundLedger ? 'BDT ' . number_format((float) $clientFundLedger->balance_before, 2) : '-' }}</p>
            <p><strong>Fund Balance After:</strong> {{ $clientFundLedger ? 'BDT ' . number_format((float) $clientFundLedger->balance_after, 2) : '-' }}</p>
        </div>
    </div>

    <div class="card">
        <h2>Accounting References</h2>
        <p><strong>Receipt Number:</strong> {{ $payment->receiptNumber() }}</p>
        <p><strong>Finance Ledger ID:</strong> {{ $financeLedger?->id ?: '-' }}</p>
        <p><strong>Client Fund Ledger ID:</strong> {{ $clientFundLedger?->id ?: '-' }}</p>
    </div>

    <div class="card">
        <h2>Related Payrolls Funded</h2>
        <div class="table-wrap">
            <table>
                <tr><th>Payroll</th><th>Employee</th><th>Paid</th><th>Status</th><th>Action</th></tr>
                @forelse($relatedPayrolls as $payroll)
                    <tr>
                        <td>{{ $payroll->salaryReceiptNumber() }}</td>
                        <td>{{ $payroll->employee?->name ?: '-' }}</td>
                        <td>BDT {{ number_format($payroll->paid_amount, 2) }}</td>
                        <td>{{ $payroll->payrollStatusLabel() }}</td>
                        <td><a href="/admin/payroll/{{ $payroll->id }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5">No related paid payrolls found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>

    <div class="card">
        <h2>Approval Timeline</h2>
        <p>Submitted: {{ $payment->created_at?->format('Y-m-d H:i') }}</p>
        <p>Pending Review</p>
        <p>Approved: {{ $payment->approved_at?->format('Y-m-d H:i') ?: '-' }}</p>
        <p>Finance Ledger Created: {{ $financeLedger ? '#' . $financeLedger->id : '-' }}</p>
        <p>Client Fund Credited: {{ $clientFundLedger ? '#' . $clientFundLedger->id : '-' }}</p>
        <p>Completed</p>
    </div>
@endsection
