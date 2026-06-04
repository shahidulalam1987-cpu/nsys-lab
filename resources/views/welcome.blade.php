<!DOCTYPE html>
<html>
<head>
    <title>NSYS Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 30px;
        }

        h1 {
            color: #0f172a;
        }

        .cards {
            display: flex;
            gap: 15px;
        }

        .card {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 10px;
            width: 200px;
        }

        .card strong {
            font-size: 28px;
            color: #2563eb;
        }

        table {
            background: white;
            border-collapse: collapse;
            width: 500px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>

    <h1>NSYS Agency Dashboard</h1>

    <hr>

    <h3>Statistics</h3>

    <div class="cards">
        <div class="card">Total Clients<br><strong>{{ $totalClients }}</strong></div>
        <div class="card">Total Orders<br><strong>{{ $totalOrders }}</strong></div>
        <div class="card">Total Revenue<br><strong>${{ $totalRevenue }}</strong></div>
        <div class="card">Pending Payments<br><strong>${{ $pendingPayments }}</strong></div>
    </div>

    <hr>

    <h3>Recent Clients</h3>

    <table>
        <tr>
            <th>Name</th>
            <th>Phone</th>
            <th>Status</th>
        </tr>

        @foreach($clients as $client)
        <tr>
            <td>{{ $client['name'] }}</td>
            <td>{{ $client['phone'] }}</td>
            <td>{{ $client['status'] }}</td>
        </tr>
        @endforeach
    </table>

</body>
</html>