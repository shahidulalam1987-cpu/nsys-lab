<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Client Payment Receipt</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color:#111827; font-size:12px; }
        .header { border-bottom:2px solid #2563eb; padding-bottom:12px; margin-bottom:18px; }
        .logo { font-size:22px; font-weight:800; color:#2563eb; }
        table { width:100%; border-collapse:collapse; margin-top:12px; }
        td, th { border:1px solid #d1d5db; padding:8px; text-align:left; }
        th { background:#eff6ff; }
        .muted { color:#6b7280; }
    </style>
</head>
<body>
    @php
        $financeLedger = $payment->financeLedger();
        $clientFundLedger = $payment->clientFundLedger();
    @endphp
    <div class="header">
        <div class="logo">NSYS Agency</div>
        <h2>Client Payment Receipt</h2>
        <p class="muted">QR Code placeholder</p>
    </div>

    <table>
        <tr><th>Receipt Number</th><td>{{ $payment->receiptNumber() }}</td></tr>
        <tr><th>Client</th><td>{{ $payment->client?->company_name ?: '-' }}</td></tr>
        <tr><th>Amount</th><td>BDT {{ number_format($payment->amount, 2) }}</td></tr>
        <tr><th>Transaction ID</th><td>{{ $payment->transaction_id }}</td></tr>
        <tr><th>Payment Method</th><td>{{ $payment->payment_method }}</td></tr>
        <tr><th>Client Fund Type</th><td>{{ $payment->fund_type === 'facebook_ads' ? 'Facebook Ads Fund' : 'Employee Salary Fund' }}</td></tr>
        <tr><th>Status</th><td>{{ ucfirst($payment->status) }}</td></tr>
    </table>

    <h3>Approval Info</h3>
    <table>
        <tr><th>Submitted At</th><td>{{ $payment->created_at?->format('Y-m-d H:i') }}</td></tr>
        <tr><th>Approved By</th><td>{{ $payment->approver?->name ?: '-' }}</td></tr>
        <tr><th>Approved At</th><td>{{ $payment->approved_at?->format('Y-m-d H:i') ?: '-' }}</td></tr>
    </table>

    <h3>Finance & Ledger Reference</h3>
    <table>
        <tr><th>Finance Account</th><td>{{ $payment->financeAccount?->account_name ?: '-' }}</td></tr>
        <tr><th>Finance Ledger ID</th><td>{{ $financeLedger?->id ?: '-' }}</td></tr>
        <tr><th>Client Fund Ledger ID</th><td>{{ $clientFundLedger?->id ?: '-' }}</td></tr>
        <tr><th>Ledger Reference</th><td>{{ $payment->transaction_id }}</td></tr>
    </table>
</body>
</html>
