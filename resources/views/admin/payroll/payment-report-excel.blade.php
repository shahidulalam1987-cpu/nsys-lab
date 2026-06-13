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
            <th>Employee ID</th>
            <th>Client</th>
            <th>Month</th>
            <th>Salary</th>
            <th>Payment Date</th>
            <th>Finance Account</th>
            <th>Reference</th>
            <th>Status</th>
        </tr>
        @foreach($payrolls as $payroll)
            <tr>
                <td>{{ $payroll->snapshotEmployeeName() }}</td>
                <td>{{ $payroll->snapshotEmployeeCode() }}</td>
                <td>{{ $payroll->client?->company_name ?: '-' }}</td>
                <td>{{ $payroll->salary_month?->format('Y-m') ?: '-' }}</td>
                <td>{{ number_format($payroll->snapshotSalaryAmount(), 2, '.', '') }}</td>
                <td>{{ $payroll->payment_date?->toDateString() ?: '-' }}</td>
                <td>{{ $payroll->finance_account_name ?: ($payroll->financeAccount?->account_name ?: '-') }}</td>
                <td>{{ $payroll->transaction_id ?: '-' }}</td>
                <td>{{ $payroll->payrollStatusLabel() }}</td>
            </tr>
        @endforeach
    </table>
</body>
</html>
