<div class="card" style="margin-top:20px;">
    <table>
        <tr>
            <th>ID</th>
            <th>Client</th>
            <th>Salary Month</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Transaction ID</th>
            <th>Proof</th>
            <th>Status</th>
            <th>Reject Reason</th>
            <th>Action</th>
        </tr>
        @forelse($payments as $payment)
            <tr>
                <td>{{ $payment->id }}</td>
                <td>{{ $payment->client?->company_name }}</td>
                <td>{{ $payment->salary_month?->format('Y-m') }}</td>
                <td>BDT {{ number_format($payment->amount, 2) }}</td>
                <td>{{ $payment->payment_method }}</td>
                <td>{{ $payment->transaction_id }}</td>
                <td>
                    @if($payment->screenshot)
                        <a href="{{ asset('storage/' . $payment->screenshot) }}" target="_blank">View Proof</a>
                    @else
                        No Proof
                    @endif
                </td>
                <td>{{ ucfirst($payment->status) }}</td>
                <td>{{ $payment->status === 'rejected' ? $payment->reject_reason : '-' }}</td>
                <td>
                    @if($payment->status === 'pending')
                        <form method="POST" action="/admin/salary-payments/{{ $payment->id }}/approve" style="display:inline;">
                            @csrf
                            <button class="btn-success" type="submit">Approve</button>
                        </form>
                        <form method="POST" action="/admin/salary-payments/{{ $payment->id }}/reject" style="display:inline;">
                            @csrf
                            <input type="text" name="reject_reason" placeholder="Reject reason" required>
                            <button class="btn-danger" type="submit">Reject</button>
                        </form>
                    @else
                        -
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="10">No salary payments found.</td></tr>
        @endforelse
    </table>
</div>
