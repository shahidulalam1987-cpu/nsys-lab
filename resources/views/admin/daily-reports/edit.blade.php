@extends('layouts.admin')

@section('content')
    <h1>Edit Daily Report</h1>

    <a class="btn" href="/admin/daily-reports">Back to Daily Reports</a>

    @if ($errors->any())
        <div class="card" style="color:red; margin-top:20px;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="margin-top:20px;">
        <form method="POST" action="/admin/daily-reports/{{ $dailyReport->id }}/update">
            @csrf

            <p>
                Client<br>
                <select name="client_id" required>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ $dailyReport->client_id == $client->id ? 'selected' : '' }}>
                            {{ $client->company_name }}
                        </option>
                    @endforeach
                </select>
            </p>

            <p>
                Date<br>
                <input type="date" name="report_date" value="{{ $dailyReport->report_date }}" required>
            </p>

            <p>
                Page Name<br>
                <input type="text" name="page_name" value="{{ $dailyReport->page_name }}" required>
            </p>

            <p>
                Dollar Spend<br>
                <input type="number" step="0.01" name="dollar_spend" value="{{ $dailyReport->dollar_spend }}" required>
            </p>

            <p>
                Orders<br>
                <input type="number" name="orders" value="{{ $dailyReport->orders }}" required>
            </p>

            <button class="btn" type="submit">Update Report</button>
        </form>
    </div>
@endsection