<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Payment Report</title>
</head>
<body>
    <table>
        <tr>
            <th>Employee</th>
            <th>Receipt Number</th>
            <th>Employee ID</th>
            <th>Client</th>
            <th>Month</th>
            <th>Salary</th>
            <th>Payment Date</th>
            <th>Finance Account</th>
            <th>Reference</th>
            <th>Finance Ledger ID</th>
            <th>Client Fund Ledger ID</th>
            <th>Status</th>
        </tr>
        @foreach($payrolls as $payroll)
            <tr>
                <td>{{ $payroll->snapshotEmployeeName() }}</td>
                <td>{{ $payroll->salaryReceiptNumber() }}</td>
                <td>{{ $payroll->snapshotEmployeeCode() }}</td>
                <td>{{ $payroll->client?->company_name ?: '-' }}</td>
                <td>{{ $payroll->salary_month?->format('Y-m') ?: '-' }}</td>
                <td>{{ number_format($payroll->snapshotSalaryAmount(), 2, '.', '') }}</td>
                <td>{{ $payroll->payment_date?->toDateString() ?: '-' }}</td>
                <td>{{ $payroll->finance_account_name ?: ($payroll->financeAccount?->account_name ?: '-') }}</td>
                <td>{{ $payroll->transaction_id ?: '-' }}</td>
                <td>{{ $payroll->financeLedgers->firstWhere('transaction_type', 'salary_payment')?->id ?: '-' }}</td>
                <td>{{ $payroll->clientFundLedgers->firstWhere('direction', \App\Models\ClientFundLedger::DIRECTION_DEBIT)?->id ?: '-' }}</td>
                <td>{{ $payroll->payrollStatusLabel() }}</td>
            </tr>
        @endforeach
    </table>
</body>
</html>
