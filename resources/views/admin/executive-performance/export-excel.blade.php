<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Executive Dashboard Export</title>
</head>
<body>
    <h1>NSYS Lab Executive Dashboard Export</h1>
    <table>
        <tr>
            <th>Section</th>
            <th>Metric</th>
            <th>Value</th>
        </tr>
        @foreach($rows as $row)
            <tr>
                <td>{{ $row['section'] }}</td>
                <td>{{ $row['metric'] }}</td>
                <td>{{ $row['value'] }}</td>
            </tr>
        @endforeach
    </table>
</body>
</html>
