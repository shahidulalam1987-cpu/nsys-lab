@extends('layouts.admin')

@section('content')
    <h1>Client Payment History</h1>

    <a class="btn" href="/admin/salary-payments/create">Receive Client Payment</a>
    <a class="btn" href="/admin/salary-payments/pending">Pending Client Payments</a>

    <div class="card" style="margin-top:20px;">
        <form method="GET" action="/admin/salary-payments">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Receipt, transaction, ledger, client">
            <select name="client_id">
                <option value="">All Clients</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>{{ $client->company_name }}</option>
                @endforeach
            </select>
            <select name="status">
                <option value="">All Status</option>
                @foreach(['pending', 'approved', 'rejected'] as $status)
                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Filter</button>
            <a href="/admin/salary-payments">Reset</a>
        </form>
    </div>

    @include('admin.salary-payments.partials.table', [
        'payments' => $payments,
        'mode' => 'history',
        'emptyMessage' => 'No client payment history found.',
    ])
@endsection
