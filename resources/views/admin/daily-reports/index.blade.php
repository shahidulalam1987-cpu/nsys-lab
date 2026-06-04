@extends('layouts.admin')

@section('content')
    <h1>Daily Reports</h1>

    <a class="btn" href="/admin/daily-reports/create">Add Daily Report</a>

    <div class="card" style="margin-top:20px;">
        <form method="GET" action="/admin/daily-reports">
            <select name="client_id">
                <option value="">All Clients</option>

                @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                        #{{ $client->id }} - {{ $client->company_name }}
                    </option>
                @endforeach
            </select>

            <input type="date" name="from_date" value="{{ request('from_date') }}">
            <input type="date" name="to_date" value="{{ request('to_date') }}">
            <input type="text" name="page_name" placeholder="Page name" value="{{ request('page_name') }}">

            <button class="btn" type="submit">Filter</button>
            <a href="/admin/daily-reports">Reset</a>
        </form>
    </div>

    <div class="card">
        <table>
            <tr>
                <th>ID</th>
                <th>Client</th>
                <th>Date</th>
                <th>Page</th>
                <th>Dollar Spend</th>
                <th>Orders</th>
                <th>Action</th>
            </tr>

            @foreach($reports as $report)
            <tr>
                <td>{{ $report->id }}</td>
                <td>{{ $report->client->company_name ?? 'N/A' }}</td>
                <td>{{ $report->report_date }}</td>
                <td>{{ $report->page_name }}</td>
                <td>${{ number_format($report->dollar_spend, 2) }}</td>
                <td>{{ $report->orders }}</td>
                <td>
                    <a href="/admin/daily-reports/{{ $report->id }}/edit">Edit</a>

                    <form method="POST" action="/admin/daily-reports/{{ $report->id }}/delete" style="display:inline;">
                        @csrf
                        <button type="submit" onclick="return confirm('Delete this report?')">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </table>
    </div>
@endsection