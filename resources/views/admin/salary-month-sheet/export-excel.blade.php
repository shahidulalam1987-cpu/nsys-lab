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
            <th>Client</th>
            <th>Salary Period</th>
            <th>Working Days</th>
            <th>Payable Salary</th>
            <th>Paid Salary</th>
            <th>Remaining Due</th>
            <th>Status</th>
            <th>Payment Date</th>
        </tr>
        @foreach($rows as $payroll)
            <tr>
                <td>{{ trim(($payroll->employee?->employee_id ?: '-') . ' ' . ($payroll->employee?->name ?: '')) }}</td>
                <td>{{ $payroll->client?->company_name ?: '-' }}</td>
                <td>{{ $payroll->salary_period }}</td>
                <td>{{ $payroll->working_days ?? 0 }}</td>
                <td>{{ number_format($payroll->payable_salary, 2, '.', '') }}</td>
                <td>{{ number_format($payroll->paid_amount, 2, '.', '') }}</td>
                <td>{{ number_format(max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0), 2, '.', '') }}</td>
                <td>{{ $payroll->reportStatusLabel() }}</td>
                <td>{{ $payroll->payment_date?->toDateString() ?: '-' }}</td>
            </tr>
        @endforeach
    </table>
</body>
</html>
