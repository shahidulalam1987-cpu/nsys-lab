@extends('layouts.admin')

@section('content')
    <h1>Pending Payments</h1>

    <a class="btn" href="/admin/payments">All Payments</a>

    @if ($errors->any())
        <div class="card" style="color:#ef4444; margin-top:20px;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="margin-top:20px;">
        <table>
            <tr>
                <th>ID</th>
                <th>Client</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Transaction ID</th>
                <th>Proof</th>
                <th>Status</th>
                <th>Date</th>
                <th>Action</th>
            </tr>

            @forelse($payments as $payment)
                <tr>
                    <td>{{ $payment->id }}</td>
                    <td>{{ $payment->client->company_name ?? 'N/A' }}</td>
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
                    <td>{{ $payment->created_at }}</td>
                    <td>
                        <form action="/admin/payments/{{ $payment->id }}/approve" method="POST" style="display:inline;">
                            @csrf
                            <button class="btn btn-success" type="submit">Approve</button>
                        </form>

                        <br><br>

                        <form action="/admin/payments/{{ $payment->id }}/reject" method="POST">
                            @csrf
                            <input type="text" name="reject_reason" placeholder="Reject reason" required>
                            <button class="btn btn-danger" type="submit">Reject</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">No pending payments found.</td>
                </tr>
            @endforelse
        </table>
    </div>
@endsection