<div class="card" style="margin-top:20px;">
    @php
        $mode = $mode ?? 'history';
        $emptyMessage = $emptyMessage ?? 'No client payment history found.';
    @endphp

    <table>
        <tr>
            <th>ID</th>
            <th>Client</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Transaction ID</th>
            @if($mode === 'pending')
                <th>Proof</th>
                <th>Submitted Date</th>
            @else
                <th>Payment Date</th>
                <th>Proof</th>
            @endif
            <th>Status</th>
            @if($mode !== 'pending')
                <th>Reject Reason</th>
            @endif
            <th>Action</th>
        </tr>
        @forelse($payments as $payment)
            <tr>
                <td>{{ $payment->id }}</td>
                <td>{{ $payment->client?->company_name }}</td>
                <td>BDT {{ number_format($payment->amount, 2) }}</td>
                <td>{{ $payment->payment_method }}</td>
                <td>{{ $payment->transaction_id }}</td>
                @if($mode !== 'pending')
                    <td>{{ $payment->salary_month?->toDateString() ?: '-' }}</td>
                @endif
                <td>
                    @if($payment->screenshot)
                        <a href="{{ asset('storage/' . $payment->screenshot) }}" target="_blank">View Proof</a>
                    @else
                        -
                    @endif
                </td>
                @if($mode === 'pending')
                    <td>{{ $payment->created_at?->toDateString() ?: '-' }}</td>
                @endif
                <td>{{ ucfirst($payment->status) }}</td>
                @if($mode !== 'pending')
                    <td>{{ $payment->status === 'rejected' ? $payment->reject_reason : '-' }}</td>
                @endif
                <td>
                    @if($mode === 'pending' && $payment->status === 'pending')
                        <form method="POST" action="/admin/salary-payments/{{ $payment->id }}/approve" style="display:inline;">
                            @csrf
                            <select name="finance_account_id" required>
                                <option value="">Receive Into</option>
                                @foreach($financeAccounts ?? [] as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_name }} - BDT {{ number_format((float) $account->current_balance, 2) }}</option>
                                @endforeach
                            </select>
                            <button class="btn-success" type="submit">Approve</button>
                        </form>
                        <form method="POST" action="/admin/salary-payments/{{ $payment->id }}/reject" style="display:inline;">
                            @csrf
                            <input type="text" name="reject_reason" placeholder="Reject reason" required>
                            <button class="btn-danger" type="submit">Reject</button>
                        </form>
                    @else
                        <form method="POST" action="/admin/salary-payments/{{ $payment->id }}/delete" style="display:inline;">
                            @csrf
                            <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this client payment record?');">Delete</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="{{ $mode === 'pending' ? 9 : 10 }}">{{ $emptyMessage }}</td></tr>
        @endforelse
    </table>
</div>
