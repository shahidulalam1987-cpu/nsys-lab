<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Executive Dashboard PDF</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; }
        h1, h2 { margin-bottom: 6px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 18px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>Executive Dashboard</h1>
    <p>{{ $dashboard['filters']['label'] }}: {{ $dashboard['filters']['date_from'] }} to {{ $dashboard['filters']['date_to'] }}</p>

    <h2>Today</h2>
    <table>
        <tr><th>Metric</th><th>Value</th></tr>
        <tr><td>Total Orders</td><td>{{ number_format($dashboard['today']['orders']) }}</td></tr>
        <tr><td>Total Facebook Spend</td><td>USD {{ number_format($dashboard['today']['spend_usd'], 2) }} / BDT {{ number_format($dashboard['today']['spend_bdt'], 2) }}</td></tr>
        <tr><td>Total Revenue</td><td>BDT {{ number_format($dashboard['today']['revenue'], 2) }}</td></tr>
        <tr><td>Estimated Profit</td><td>BDT {{ number_format($dashboard['today']['profit'], 2) }}</td></tr>
        <tr><td>Pending Approvals</td><td>{{ number_format($dashboard['today']['pending_approvals']) }}</td></tr>
    </table>

    <h2>This Month</h2>
    <table>
        <tr><th>Metric</th><th>Value</th></tr>
        <tr><td>Total Orders</td><td>{{ number_format($dashboard['month']['orders']) }}</td></tr>
        <tr><td>Total Spend</td><td>USD {{ number_format($dashboard['month']['spend_usd'], 2) }}</td></tr>
        <tr><td>Total Revenue</td><td>BDT {{ number_format($dashboard['month']['revenue'], 2) }}</td></tr>
        <tr><td>Net Profit</td><td>BDT {{ number_format($dashboard['month']['net_profit'], 2) }}</td></tr>
    </table>
</body>
</html>
