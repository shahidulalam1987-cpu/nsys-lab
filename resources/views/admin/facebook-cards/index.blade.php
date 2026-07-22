@extends('layouts.admin')

@section('content')
    <style>
        .card-management-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .card-management-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .card-management-note {
            border: 1px solid rgba(56, 189, 248, .35);
            background: rgba(14, 165, 233, .09);
            color: #bae6fd;
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 18px;
        }

        .card-filter-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            align-items: end;
        }

        .card-filter-grid label {
            display: grid;
            gap: 8px;
            font-weight: 800;
        }

        .card-filter-grid select {
            width: 100%;
        }

        .card-name {
            color: #e5f2ff;
            font-weight: 800;
            text-decoration: none;
        }

        .card-name:hover {
            color: #38d9ff;
            text-decoration: underline;
        }

        .card-muted {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 4px;
        }

        .card-row-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            white-space: nowrap;
        }

        @media (max-width: 900px) {
            .card-management-header {
                display: block;
            }

            .card-management-actions {
                justify-content: flex-start;
                margin-top: 12px;
            }

            .card-filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="card-management-header">
        <div>
            <h1>Card Management</h1>
            <p>Monitor Facebook payment cards, balances, loads, transactions, and Binance purchase sources.</p>
        </div>
        <div class="card-management-actions">
            <a class="btn" href="/admin/facebook-cards/create">Add Card</a>
            <a class="btn" href="/admin/facebook-financial/card-loads">Load Card</a>
            <a class="btn" href="/admin/facebook-financial/card-transactions">Add Transaction</a>
        </div>
    </div>

    @include('admin.facebook-cards.partials.tabs')

    <div class="card-management-note">
        Card balances are updated through opening balance, manual adjustment, card loads, and card transactions. Balance changes create ledger-backed records.
    </div>

    <div id="overview" class="stats-grid">
        <div class="stat-card"><p>Total Card Balance</p><h2>USD {{ number_format($summary['total_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>RedotPay Balance</p><h2>USD {{ number_format($summary['redotpay_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>Tevau Balance</p><h2>USD {{ number_format($summary['tavao_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>Low Balance Cards</p><h2>{{ number_format($summary['low_balance']) }}</h2></div>
        <div class="stat-card"><p>Disabled Cards</p><h2>{{ number_format($summary['disabled']) }}</h2></div>
        <div class="stat-card"><p>Expired Cards</p><h2>{{ number_format($summary['expired']) }}</h2></div>
    </div>

    <div class="card">
        <h2>Filters</h2>
        <form method="GET" action="/admin/facebook-cards" class="card-filter-grid">
            <label>
                Provider
                <select name="provider">
                    <option value="">All Providers</option>
                    @foreach($providers as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['provider'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Status
                <select name="status">
                    <option value="">All Status</option>
                    @foreach(\App\Models\FacebookCard::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <div>
                <button class="btn" type="submit">Filter</button>
                <a href="/admin/facebook-cards" style="margin-left:12px;">Reset</a>
            </div>
        </form>
    </div>

    <div id="cards" class="card table-wrap">
        <h2>Cards</h2>
        <table>
            <tr>
                <th>Card</th>
                <th>Provider</th>
                <th>Assigned Ad Account</th>
                <th>Current Balance</th>
                <th>Movement</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            @forelse($cards as $card)
                <tr>
                    <td>
                        <a class="card-name" href="/admin/facebook-cards/{{ $card->id }}">{{ $card->card_name }}</a>
                        <div class="card-muted">{{ $card->card_type ?: 'Card' }} | Last 4: {{ $card->card_last_four ?: '-' }}</div>
                    </td>
                    <td>{{ $card->providerLabel() }}</td>
                    <td>{{ $card->adAccount?->ad_account_name ?: '-' }}</td>
                    <td>USD {{ number_format((float) $card->current_balance, 2) }}</td>
                    <td>
                        <div class="card-muted">Loads: {{ number_format($card->loads_count) }}</div>
                        <div class="card-muted">Transactions: {{ number_format($card->transactions_count) }}</div>
                    </td>
                    <td><span class="badge {{ $card->statusBadgeClass() }}">{{ $card->statusLabel() }}</span></td>
                    <td>
                        <div class="card-row-actions">
                            <a class="btn" href="/admin/facebook-cards/{{ $card->id }}">View</a>
                            <a class="btn" href="/admin/facebook-cards/{{ $card->id }}/edit">Edit</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">No cards found.</td></tr>
            @endforelse
        </table>
    </div>

    <div id="statement" class="card">
        <h2>Statement</h2>
        <p>Use the Loads, Transactions, and Binance Purchases tabs to review card movement history.</p>
    </div>
@endsection
