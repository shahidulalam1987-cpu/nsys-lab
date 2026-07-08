@extends('layouts.admin')

@section('content')
    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <h1>Card Balance</h1>
            <p>Monitor Facebook payment cards. This is read-only for alerting unless an admin updates card balance manually.</p>
        </div>
        <a class="btn" href="/admin/facebook-cards/create">Add Card</a>
    </div>

    @include('admin.facebook-cards.partials.tabs')

    <div id="overview" class="stats-grid">
        <div class="stat-card"><p>Total Card Balance</p><h2>USD {{ number_format($summary['total_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>RedotPay Balance</p><h2>USD {{ number_format($summary['redotpay_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>Tevau Balance</p><h2>USD {{ number_format($summary['tavao_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>Low Balance Cards</p><h2>{{ number_format($summary['low_balance']) }}</h2></div>
        <div class="stat-card"><p>Disabled Cards</p><h2>{{ number_format($summary['disabled']) }}</h2></div>
        <div class="stat-card"><p>Expired Cards</p><h2>{{ number_format($summary['expired']) }}</h2></div>
    </div>

    <div id="cards" class="card">
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Card</th>
                    <th>Last 4</th>
                    <th>Provider</th>
                    <th>Assigned Ad Account</th>
                    <th>Current Balance</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                @forelse($cards as $card)
                    <tr>
                        <td><a href="/admin/facebook-cards/{{ $card->id }}">{{ $card->card_name }}</a><br><small>{{ $card->card_type ?: '-' }}</small></td>
                        <td>{{ $card->card_last_four ?: '-' }}</td>
                        <td>{{ $card->provider ?: '-' }}</td>
                        <td>{{ $card->adAccount?->ad_account_name ?: '-' }}</td>
                        <td>USD {{ number_format((float) $card->current_balance, 2) }}</td>
                        <td><span class="badge {{ $card->statusBadgeClass() }}">{{ $card->statusLabel() }}</span></td>
                        <td style="white-space:nowrap;">
                            <a href="/admin/facebook-cards/{{ $card->id }}">View</a> |
                            <a href="/admin/facebook-cards/{{ $card->id }}/edit">Edit</a>
                            <form method="POST" action="/admin/facebook-cards/{{ $card->id }}/balance" style="display:inline-flex;gap:6px;align-items:center;margin-left:8px;">
                                @csrf
                                <input type="number" step="0.01" name="current_balance" value="{{ $card->current_balance }}" style="width:110px;">
                                <input type="text" name="adjustment_reason" placeholder="Reason" required style="width:130px;">
                                <button class="btn" type="submit">Update Balance</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">No cards found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>

    <div id="statement" class="card">
        <h2>Statement</h2>
        <p>Use the Loads, Transactions, and Binance Purchases tabs to review card movement history.</p>
    </div>
@endsection
