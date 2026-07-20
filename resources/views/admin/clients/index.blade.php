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
                <th>Salary Fund</th>
                <th>Ads Fund</th>
                <th>Combined Balance</th>
                <th>Pending Payments</th>
                <th>Status</th>
                <th>Action</th>
            </tr>

            @foreach($clients as $client)
                @php
                    $fund = $fundRows->get($client->id);
                    $combined = (float) ($fund['combined_balance'] ?? 0);
                    $balanceTone = $combined < 0 ? 'badge-danger' : 'badge-success';
                @endphp
                <tr>
                    <td>{{ $client->id }}</td>
                    <td>
                        <a href="/admin/clients/{{ $client->id }}">
                            {{ $client->company_name }}
                        </a>
                    </td>
                    <td>{{ $client->phone }}</td>
                    <td>BDT {{ number_format((float) ($fund['available_balance'] ?? 0), 2) }}</td>
                    <td>BDT {{ number_format((float) ($fund['ads_balance'] ?? 0), 2) }}</td>
                    <td><span class="badge {{ $balanceTone }}">BDT {{ number_format(abs($combined), 2) }} {{ $combined < 0 ? 'Due' : 'Available' }}</span></td>
                    <td>
                        BDT {{ number_format((float) ($fund['pending_payments'] ?? 0), 2) }}
                        <br><span style="color:var(--muted);">{{ number_format((int) ($fund['pending_payment_count'] ?? 0)) }} pending</span>
                    </td>
                    <td>
                        @if($client->status == 'active')
                            <span class="badge badge-success">Active</span>
                        @elseif($client->status == 'pending')
                            <span class="badge badge-warning">Pending</span>
                        @else
                            <span class="badge badge-danger">Inactive</span>
                        @endif
                    </td>
                    <td><a class="btn" href="/admin/clients/{{ $client->id }}">View</a></td>
                </tr>
            @endforeach
        </table>
    </div>
@endsection
