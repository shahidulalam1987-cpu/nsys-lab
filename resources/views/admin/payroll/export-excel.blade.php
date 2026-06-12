<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Generate Report</title>
</head>
<body>
    <table>
        <tr>
            <th>Employee</th>
            <th>Client</th>
            <th>Salary Source</th>
            <th>Salary Period</th>
            <th>Salary Date</th>
            <th>Working Days</th>
            <th>Payable Salary</th>
            <th>Paid Salary</th>
            <th>Remaining Due</th>
            <th>Status</th>
            <th>Payment Date</th>
            <th>Method</th>
            <th>Reference</th>
        </tr>
        @foreach($rows as $row)
            <tr>
                <td>{{ $row['employee'] }}</td>
                <td>{{ $row['client'] }}</td>
                <td>{{ $row['salary_source'] }}</td>
                <td>{{ $row['salary_period'] }}</td>
                <td>{{ $row['salary_date'] }}</td>
                <td>{{ $row['working_days'] }}</td>
                <td>{{ number_format($row['payable_salary'], 2, '.', '') }}</td>
                <td>{{ number_format($row['paid_salary'], 2, '.', '') }}</td>
                <td>{{ number_format($row['remaining_due'], 2, '.', '') }}</td>
                <td>{{ $row['status'] }}</td>
                <td>{{ $row['payment_date'] }}</td>
                <td>{{ $row['method'] }}</td>
                <td>{{ $row['reference'] }}</td>
            </tr>
        @endforeach
    </table>
</body>
</html>
