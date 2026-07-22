@extends('layouts.admin')

@section('content')
    <style>
        .finance-accounts-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
        }

        .finance-accounts-header p {
            max-width: 760px;
        }

        .finance-accounts-note {
            border: 1px solid rgba(56, 189, 248, .35);
            background: rgba(14, 165, 233, .09);
            color: #bae6fd;
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 18px;
        }

        .finance-account-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            align-items: end;
        }

        .finance-account-grid label {
            display: grid;
            gap: 8px;
            font-weight: 800;
        }

        .finance-account-grid input,
        .finance-account-grid select,
        .finance-account-grid textarea {
            width: 100%;
            min-width: 0;
        }

        .finance-account-grid .wide {
            grid-column: span 2;
        }

        .finance-account-name {
            color: #e5f2ff;
            font-weight: 800;
        }

        .finance-muted {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 4px;
        }

        .finance-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .protected-label {
            color: #94a3b8;
            font-size: 13px;
            font-weight: 800;
        }

        @media (max-width: 1180px) {
            .finance-account-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .finance-accounts-header {
                display: block;
            }

            .finance-account-grid {
                grid-template-columns: 1fr;
            }

            .finance-account-grid .wide {
                grid-column: span 1;
            }
        }
    </style>

    <div class="finance-accounts-header">
        <div>
            <h1>Finance Accounts</h1>
            <p>Track NSYS bank, cash, mobile wallet, Binance, RedotPay, and Tavao balances.</p>
        </div>
        <div class="finance-actions">
            <a class="btn" href="/admin/finance/reports/balance-sheet">Balance Sheet</a>
            <a class="btn" href="/admin/finance/reports/reconciliation">Reconciliation</a>
        </div>
    </div>

    <div class="finance-accounts-note">
        Opening balances and balance changes are recorded through finance ledger entries. Existing transaction history protects accounts from deletion.
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Total Accounts</p><h2>{{ number_format($summary['total_accounts']) }}</h2></div>
        <div class="stat-card"><p>Active Accounts</p><h2>{{ number_format($summary['active_accounts']) }}</h2></div>
        <div class="stat-card"><p>BDT Balance</p><h2>BDT {{ number_format($summary['bdt_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>USD Balance</p><h2>USD {{ number_format($summary['usd_balance'], 2) }}</h2></div>
    </div>

    <div class="card">
        <h2>Filters</h2>
        <form method="GET" action="/admin/finance/accounts" class="finance-account-grid">
            <label>
                Account Type
                <select name="account_type">
                    <option value="">All Types</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['account_type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Currency
                <select name="currency">
                    <option value="">All Currency</option>
                    @foreach($currencies as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['currency'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Status
                <select name="status">
                    <option value="">All Status</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <div>
                <button class="btn" type="submit">Filter</button>
                <a href="/admin/finance/accounts" style="margin-left:12px;">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Add Finance Account</h2>
        <form method="POST" action="/admin/finance/accounts" class="finance-account-grid">
            @csrf
            @include('admin.finance.partials.account-fields', ['account' => null])
            <button class="btn" type="submit">Save Account</button>
        </form>
    </div>

    <div class="card table-wrap">
        <h2>Accounts</h2>
        <table>
            <tr>
                <th>Account</th>
                <th>Type</th>
                <th>Provider / Bank</th>
                <th>Currency</th>
                <th>Current Balance</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            @forelse($accounts as $account)
                @php
                    $statusClass = [
                        'active' => 'badge-success',
                        'inactive' => 'badge-neutral',
                    ][$account->status] ?? 'badge-neutral';
                @endphp
                <tr>
                    <td>
                        <div class="finance-account-name">{{ $account->account_name }}</div>
                        <div class="finance-muted">{{ $account->account_number ?: 'No account number' }}</div>
                    </td>
                    <td>{{ $account->typeLabel() }}</td>
                    <td>{{ $account->provider_name ?: '-' }}</td>
                    <td>{{ $account->currency }}</td>
                    <td>{{ $account->currency }} {{ number_format((float) $account->current_balance, 2) }}</td>
                    <td><span class="badge {{ $statusClass }}">{{ $account->statusLabel() }}</span></td>
                    <td>
                        <div class="finance-actions">
                            <a class="btn" href="/admin/finance/accounts/{{ $account->id }}/edit">Edit</a>
                            @if($account->ledgers_count > 0)
                                <span class="protected-label">Protected</span>
                            @else
                                <form method="POST" action="/admin/finance/accounts/{{ $account->id }}/delete">
                                    @csrf
                                    <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this finance account?');">Delete</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">No finance accounts found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
