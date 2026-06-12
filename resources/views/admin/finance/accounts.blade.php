@extends('layouts.admin')

@section('content')
    <h1>Finance Accounts</h1>
    <p>Track NSYS bank, cash, mobile wallet, Binance, RedotPay, and Tavao balances.</p>

    <div class="card">
        <h2>Add Finance Account</h2>
        <form method="POST" action="/admin/finance/accounts" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;align-items:end;">
            @csrf
            @include('admin.finance.partials.account-fields', ['account' => null])
            <button class="btn" type="submit">Save Account</button>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Account Type</th>
                    <th>Account Name</th>
                    <th>Provider / Bank</th>
                    <th>Account Number</th>
                    <th>Currency</th>
                    <th>Current Balance</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                @forelse($accounts as $account)
                    <tr>
                        <td>{{ $account->typeLabel() }}</td>
                        <td>{{ $account->account_name }}</td>
                        <td>{{ $account->provider_name ?: '-' }}</td>
                        <td>{{ $account->account_number ?: '-' }}</td>
                        <td>{{ $account->currency }}</td>
                        <td>{{ $account->currency }} {{ number_format((float) $account->current_balance, 2) }}</td>
                        <td>{{ $account->statusLabel() }}</td>
                        <td style="white-space:nowrap;">
                            <a href="/admin/finance/accounts/{{ $account->id }}/edit">Edit</a>
                            <form method="POST" action="/admin/finance/accounts/{{ $account->id }}/delete" style="display:inline;">
                                @csrf
                                <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this finance account?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">No finance accounts found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
