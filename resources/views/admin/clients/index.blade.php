@extends('layouts.admin')

@section('content')
    <h1>Client Management</h1>

    <a class="btn" href="/admin/clients/create">Add Client</a>

    <div class="card" style="margin-top:20px;">
        <form method="GET" action="/admin/clients">
            <input type="text" name="company_name" placeholder="Company name" value="{{ request('company_name') }}">
            <input type="text" name="phone" placeholder="Phone" value="{{ request('phone') }}">

            <select name="status">
                <option value="">All Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>

            <button class="btn" type="submit">Search</button>
            <a href="/admin/clients">Reset</a>
        </form>

        <p>Total Clients Found: {{ $clients->count() }}</p>
    </div>

    <div class="card">
        <table>
            <tr>
                <th>ID</th>
                <th>Company</th>
                <th>Phone</th>
                <th>Total Payment</th>
                <th>Spend USD</th>
                <th>Spend BDT</th>
                <th>Orders</th>
                <th>Balance</th>
                <th>Profit</th>
                <th>Status</th>
            </tr>

            @foreach($clients as $client)
            <tr>
                <td>{{ $client->id }}</td>
                <td>
                    <a href="/admin/clients/{{ $client->id }}">
                        {{ $client->company_name }}
                    </a>
                </td>
                <td>{{ $client->phone }}</td>
                <td>৳{{ number_format($client->total_payment, 2) }}</td>
                <td>${{ number_format($client->total_dollar_spend, 2) }}</td>
                <td>৳{{ number_format($client->total_spend_bdt, 2) }}</td>
                <td>{{ $client->total_orders }}</td>
                <td>
                    @if($client->balance >= 0)
                        +৳{{ number_format($client->balance, 2) }}
                    @else
                        -৳{{ number_format(abs($client->balance), 2) }}
                    @endif
                </td>
                <td>৳{{ number_format($client->total_profit, 2) }}</td>
                <td>
    @if($client->status == 'active')
        <span class="badge badge-success">Active</span>
    @elseif($client->status == 'pending')
        <span class="badge badge-warning">Pending</span>
    @else
        <span class="badge badge-danger">Inactive</span>
    @endif
</td>
            </tr>
            @endforeach
        </table>
    </div>
@endsection