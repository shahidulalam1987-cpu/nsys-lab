@extends('layouts.admin')

@section('content')
    <h1>All Payments</h1>

    <a class="btn" href="/admin/payments/pending">Pending Payments</a>

    <div class="card" style="margin-top:20px;">
        <form method="GET" action="/admin/payments">
            <select name="client_id">
                <option value="">All Clients</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                        {{ $client->company_name }}
                    </option>
                @endforeach
            </select>

            <select name="status">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>

            <select name="payment_method">
                <option value="">All Methods</option>
                <option value="bKash" {{ request('payment_method') == 'bKash' ? 'selected' : '' }}>bKash</option>
                <option value="Nagad" {{ request('payment_method') == 'Nagad' ? 'selected' : '' }}>Nagad</option>
                <option value="Rocket" {{ request('payment_method') == 'Rocket' ? 'selected' : '' }}>Rocket</option>
                <option value="Bank" {{ request('payment_method') == 'Bank' ? 'selected' : '' }}>Bank</option>
                <option value="Cash" {{ request('payment_method') == 'Cash' ? 'selected' : '' }}>Cash</option>
                <option value="Invoice" {{ request('payment_method') == 'Invoice' ? 'selected' : '' }}>Invoice</option>
            </select>

            <input type="date" name="from_date" value="{{ request('from_date') }}">
            <input type="date" name="to_date" value="{{ request('to_date') }}">

            <button class="btn" type="submit">Filter</button>
            <a href="/admin/payments">Reset</a>
        </form>

        <p>Total Payments Found: {{ $payments->count() }}</p>
    </div>

    <div class="card">
        <table>
            <tr>
                <th>ID</th>
                <th>Client</th>
                <th>Invoice No</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Transaction ID</th>
                <th>Proof</th>
                <th>Status</th>
                <th>Reject Reason</th>
                <th>Date</th>
            </tr>

            @forelse($payments as $payment)
                <tr>
                    <td>{{ $payment->id }}</td>
                    <td>{{ $payment->client->company_name ?? 'N/A' }}</td>
                    <td>
                        @if($payment->invoice)
                            {{ $payment->invoice->invoice_number }}
                        @else
                            -
                        @endif
                    </td>
                    <td>৳{{ number_format($payment->amount, 2) }}</td>
                    <td>{{ $payment->payment_method }}</td>
                    <td>{{ $payment->transaction_id }}</td>
                    <td>
                        @if($payment->screenshot)
                            <a href="{{ asset('storage/' . $payment->screenshot) }}" target="_blank">View Proof</a>
                        @else
                            No Proof
                        @endif
                    </td>
                    <td>
                        @if($payment->status == 'approved')
                            <span class="badge badge-success">Approved</span>
                        @elseif($payment->status == 'pending')
                            <span class="badge badge-warning">Pending</span>
                        @else
                            <span class="badge badge-danger">Rejected</span>
                        @endif
                    </td>
                    <td>
                        @if($payment->status === 'rejected')
                            {{ $payment->reject_reason }}
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $payment->created_at }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10">No payments found.</td>
                </tr>
            @endforelse
        </table>
    </div>
@endsection