@extends('layouts.admin')

@section('content')
    <h1>Profit History</h1>

    <div class="card">
        <h2>Total Profit: ৳{{ number_format($totalProfit, 2) }}</h2>
    </div>

    <div class="card">
        <table>
            <tr>
                <th>ID</th>
                <th>Client</th>
                <th>Date</th>
                <th>Dollar Spend</th>
                <th>Client Rate</th>
                <th>Buy Rate</th>
                <th>Profit</th>
            </tr>

            @foreach($reports as $report)
            <tr>
                <td>{{ $report->id }}</td>
                <td>{{ $report->client->company_name ?? 'N/A' }}</td>
                <td>{{ $report->report_date }}</td>
                <td>${{ number_format($report->dollar_spend, 2) }}</td>
                <td>{{ $report->client->client_rate ?? 0 }}</td>
                <td>{{ $report->client->buy_rate ?? 0 }}</td>
                <td>৳{{ number_format($report->profit, 2) }}</td>
            </tr>
            @endforeach
        </table>
    </div>
@endsection