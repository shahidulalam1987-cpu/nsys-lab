<table>
    <tr>
        <th colspan="11">Salary Ledger - {{ $employee->employee_id }} {{ $employee->name }}</th>
    </tr>
    <tr>
        <th>Month</th>
        <th>Client</th>
        <th>Working Days</th>
        <th>Non Working Days</th>
        <th>Generated Salary</th>
        <th>Paid Amount</th>
        <th>Due Amount</th>
        <th>Ledger Type</th>
        <th>Status</th>
        <th>Generated Date</th>
        <th>Paid Date</th>
    </tr>
    @foreach($rows as $row)
        <tr>
            <td>{{ $row['month'] }}</td>
            <td>{{ $row['client'] }}</td>
            <td>{{ $row['working_days'] }}</td>
            <td>{{ $row['non_working_days'] }}</td>
            <td>{{ number_format($row['generated_salary'], 2, '.', '') }}</td>
            <td>{{ number_format($row['paid_amount'], 2, '.', '') }}</td>
            <td>{{ number_format($row['due_amount'], 2, '.', '') }}</td>
            <td>{{ $row['history_status'] }}</td>
            <td>{{ $row['status'] }}</td>
            <td>{{ $row['generated_date'] }}</td>
            <td>{{ $row['paid_date'] }}</td>
        </tr>
    @endforeach
</table>
