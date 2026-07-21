@extends('layouts.admin')

@section('content')
    <style>
        .ledger-filter-form {
            align-items: end;
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        }

        .ledger-filter-actions {
            align-items: center;
            display: flex;
            gap: 10px;
        }

        .ledger-reset {
            color: var(--muted);
            font-size: 13px;
            text-decoration: none;
        }

        .ledger-reset:hover {
            color: var(--cyan);
        }

        .ledger-action {
            border-radius: 9px;
            font-size: 12px;
            min-height: 34px;
            padding: 8px 11px;
        }

        .ledger-type {
            border-radius: 999px;
            display: inline-block;
            font-size: 12px;
            font-weight: 800;
            padding: 6px 10px;
        }

        .ledger-type-credit,
        .ledger-type-billing_paid { background: rgba(34, 197, 94, .16); color: #86efac; }
        .ledger-type-debit { background: rgba(239, 68, 68, .16); color: #fca5a5; }
        .ledger-type-status_change { background: rgba(47, 140, 255, .18); color: #93c5fd; }
        .ledger-type-threshold_update,
        .ledger-type-balance_adjustment { background: rgba(245, 158, 11, .18); color: #fcd34d; }
    </style>

    <h1>Ad Account Financial Ledger</h1>
    <p>Track threshold updates, balance adjustments, billing payments, credits, debits, and status changes.</p>

    <div class="stats-grid">
        <div class="stat-card"><p>Total Entries</p><h2>{{ number_format($summary['total']) }}</h2></div>
        <div class="stat-card"><p>Total Credits</p><h2>USD {{ number_format($summary['credits'], 2) }}</h2></div>
        <div class="stat-card"><p>Total Debits</p><h2>USD {{ number_format($summary['debits'], 2) }}</h2></div>
        <div class="stat-card"><p>Billing Paid</p><h2>{{ number_format($summary['billing_paid']) }}</h2></div>
        <div class="stat-card"><p>Status Changes</p><h2>{{ number_format($summary['status_changes']) }}</h2></div>
    </div>

    <div class="card">
        <form class="ledger-filter-form" method="GET" action="/admin/ad-account-ledger">
            <label>Ad Account<br>
                <select name="ad_account_id">
                    <option value="">All Ad Accounts</option>
                    @foreach($adAccounts as $account)
                        <option value="{{ $account->id }}" @selected(($filters['ad_account_id'] ?? '') == $account->id)>{{ $account->ad_account_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Transaction Type<br>
                <select name="transaction_type">
                    <option value="">All Types</option>
                    @foreach($transactionTypes as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['transaction_type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>From<br><input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></label>
            <label>To<br><input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></label>
            <div class="ledger-filter-actions">
            <button class="btn" type="submit">Filter</button>
                <a class="ledger-reset" href="/admin/ad-account-ledger">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Ad Account</th>
                    <th>Transaction Type</th>
                    <th>Amount</th>
                    <th>Previous Value</th>
                    <th>New Value</th>
                    <th>Created By</th>
                    <th>Actions</th>
                </tr>
                @forelse($ledgers as $ledger)
                    <tr>
                        <td>{{ $ledger->transaction_date?->toDateString() }}</td>
                        <td>
                            @if($ledger->adAccount)
                                <a href="/admin/ad-accounts/{{ $ledger->adAccount->id }}">{{ $ledger->adAccount->ad_account_name }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @php
                                $typeClass = match ($ledger->transaction_type) {
                                    'manual_credit' => 'ledger-type-credit',
                                    'manual_debit' => 'ledger-type-debit',
                                    default => 'ledger-type-' . $ledger->transaction_type,
                                };
                            @endphp
                            <span class="ledger-type {{ $typeClass }}">{{ $ledger->typeLabel() }}</span>
                        </td>
                        <td>USD {{ number_format((float) $ledger->amount, 2) }}</td>
                        <td>{{ $ledger->previous_value !== null ? 'USD ' . number_format((float) $ledger->previous_value, 2) : '-' }}</td>
                        <td>{{ $ledger->new_value !== null ? 'USD ' . number_format((float) $ledger->new_value, 2) : '-' }}</td>
                        <td>{{ $ledger->creator?->name ?: '-' }}</td>
                        <td><a class="btn ledger-action" href="/admin/ad-account-ledger/{{ $ledger->id }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8">No ledger records found.</td></tr>
                @endforelse
            </table>
        </div>
        {{ $ledgers->links() }}
    </div>
@endsection
