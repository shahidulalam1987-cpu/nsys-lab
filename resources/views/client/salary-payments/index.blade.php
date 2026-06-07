@extends('layouts.client')

@section('content')
    <h1>Client Fund Payment History</h1>

    <a class="btn" href="/client/salary-payments/create">Submit Client Fund Payment</a>
    <a class="btn" href="/client/salary-fund">Salary Fund Summary</a>

    <div class="card" style="margin-top:20px;">
        <form method="GET" action="/client/salary-payments">
            <select name="status">
                <option value="">All Status</option>
                @foreach(['pending', 'approved', 'rejected'] as $status)
                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Filter</button>
            <a href="/client/salary-payments">Reset</a>
        </form>
    </div>

    <div class="card">
        <table>
            <tr>
                <th>Payment Date</th>
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
                    <td>{{ $payment->salary_month?->toDateString() }}</td>
                    <td>BDT {{ number_format($payment->amount, 2) }}</td>
                    <td>{{ $payment->payment_method }}</td>
                    <td>{{ $payment->transaction_id }}</td>
                    <td>
                        @if($payment->screenshot)
                            <a href="{{ asset('storage/' . $payment->screenshot) }}" target="_blank">View Proof</a>
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ ucfirst($payment->status) }}</td>
                    <td>{{ $payment->status === 'rejected' ? $payment->reject_reason : '-' }}</td>
                    <td>{{ $payment->created_at }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No client fund payments found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
