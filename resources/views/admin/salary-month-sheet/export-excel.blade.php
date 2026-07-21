<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Report</title>
</head>
<body>
    <table>
        <tr>
            <th>Employee</th>
            <th>Receipt Number</th>
            <th>Client</th>
            <th>Salary Month</th>
            <th>Payment Month</th>
            <th>Salary Period</th>
            <th>Working Days</th>
            <th>Payable Salary</th>
            <th>Paid Salary</th>
            <th>Remaining Due</th>
            <th>Status</th>
            <th>Payment Source Status</th>
            <th>Payment Date</th>
            <th>Finance Ledger ID</th>
            <th>Client Fund Ledger ID</th>
        </tr>
        @foreach($rows as $payroll)
            @php
                $paymentDate = $payroll->payment_date ?: $payroll->payment_confirmed_at ?: $payroll->paid_at;
            @endphp
            <tr>
                <td>{{ trim(($payroll->employee?->employee_id ?: '-') . ' ' . ($payroll->employee?->name ?: '')) }}</td>
                <td>{{ $payroll->salaryReceiptNumber() }}</td>
                <td>{{ $payroll->client?->company_name ?: '-' }}</td>
                <td>{{ $payroll->salary_month?->format('Y-m') ?: '-' }}</td>
                <td>{{ $paymentDate ? $paymentDate->format('Y-m') : '-' }}</td>
                <td>{{ $payroll->salary_period }}</td>
                <td>{{ $payroll->working_days ?? 0 }}</td>
                <td>{{ number_format($payroll->payable_salary, 2, '.', '') }}</td>
                <td>{{ number_format($payroll->paid_amount, 2, '.', '') }}</td>
                <td>{{ number_format(max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0), 2, '.', '') }}</td>
                <td>{{ $payroll->reportStatusLabel() }}</td>
                <td>{{ $payroll->paymentSourceStatusLabel() }}</td>
                <td>{{ $paymentDate?->toDateString() ?: '-' }}</td>
                <td>{{ $payroll->financeLedgers->firstWhere('transaction_type', 'salary_payment')?->id ?: '-' }}</td>
                <td>{{ $payroll->clientFundLedgers->firstWhere('direction', \App\Models\ClientFundLedger::DIRECTION_DEBIT)?->id ?: '-' }}</td>
            </tr>
        @endforeach
    </table>
</body>
</html>
