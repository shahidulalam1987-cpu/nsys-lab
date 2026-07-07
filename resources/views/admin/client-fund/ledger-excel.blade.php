<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Client Fund Ledger</title>
</head>
<body>
    <table>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Reference</th>
            <th>Description</th>
            <th>Credit</th>
            <th>Debit</th>
            <th>Running Balance</th>
        </tr>
        @foreach($rows as $row)
            <tr>
                <td>{{ $row['date'] }}</td>
                <td>{{ $row['type'] }}</td>
                <td>{{ $row['reference'] }}</td>
                <td>{{ $row['description'] }}</td>
                <td>{{ number_format($row['credit'], 2, '.', '') }}</td>
                <td>{{ number_format($row['debit'], 2, '.', '') }}</td>
                <td>{{ number_format($row['running_balance'], 2, '.', '') }}</td>
            </tr>
        @endforeach
    </table>
</body>
</html>
