<table>
    <tr>
        <th>Client</th>
        <th>Fund Received</th>
        <th>Salary Used</th>
        <th>Balance</th>
        <th>Pending Payments</th>
        <th>Upcoming Salary</th>
        <th>Unpaid Salary</th>
    </tr>
    @foreach($rows as $row)
        <tr>
            <td>{{ $row['client'] }}</td>
            <td>{{ number_format($row['fund_received'], 2, '.', '') }}</td>
            <td>{{ number_format($row['salary_used'], 2, '.', '') }}</td>
            <td>{{ number_format($row['balance'], 2, '.', '') }}</td>
            <td>{{ number_format($row['pending_payments'], 2, '.', '') }}</td>
            <td>{{ number_format($row['upcoming_salary'], 2, '.', '') }}</td>
            <td>{{ number_format($row['unpaid_salary'], 2, '.', '') }}</td>
        </tr>
    @endforeach
</table>
