@extends('layouts.admin')

@section('content')
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <h1>Finance</h1>
            <p>Funding Dashboard: real-time USD funding visibility for Binance and payment card sources.</p>
        </div>
        <a class="btn" href="/admin/facebook-financial/funding-dashboard/update">Manual Update</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Binance Balance</p><h2>USD {{ number_format($summary['binance_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>RedotPay Balance</p><h2>USD {{ number_format($summary['redotpay_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>Tavao Balance</p><h2>USD {{ number_format($summary['tavao_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>Total Available USD</p><h2>USD {{ number_format($summary['total_available_usd'], 2) }}</h2></div>
        <div class="stat-card"><p>RedotPay Card Balance</p><h2>USD {{ number_format($summary['redotpay_card_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>Tavao Card Balance</p><h2>USD {{ number_format($summary['tavao_card_balance'], 2) }}</h2></div>
        <div class="stat-card"><p>Total Card Balance</p><h2>USD {{ number_format($summary['total_card_balance'], 2) }}</h2></div>
    </div>

    <div class="card">
        <h2>Low Balance Alerts</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <p>Low Binance Balance</p>
                <h2>{{ $summary['low_binance'] ? 'Warning' : 'Healthy' }}</h2>
                <span class="badge {{ $summary['low_binance'] ? 'badge-warning' : 'badge-success' }}">Below USD 200</span>
            </div>
            <div class="stat-card">
                <p>Low RedotPay Balance</p>
                <h2>{{ $summary['low_redotpay'] ? 'Warning' : 'Healthy' }}</h2>
                <span class="badge {{ $summary['low_redotpay'] ? 'badge-warning' : 'badge-success' }}">Below USD 100</span>
            </div>
            <div class="stat-card">
                <p>Low Tavao Balance</p>
                <h2>{{ $summary['low_tavao'] ? 'Warning' : 'Healthy' }}</h2>
                <span class="badge {{ $summary['low_tavao'] ? 'badge-warning' : 'badge-success' }}">Below USD 100</span>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Funding Sources</h2>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Source</th>
                    <th>Current Balance</th>
                    <th>Last Updated</th>
                    <th>Status</th>
                    <th>Notes</th>
                </tr>
                @foreach($balanceRows as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>USD {{ number_format($row['current_balance'], 2) }}</td>
                        <td>{{ $row['last_updated'] ? $row['last_updated']->format('Y-m-d h:i A') : '-' }}</td>
                        <td><span class="badge {{ $row['status_class'] }}">{{ $row['status'] }}</span></td>
                        <td>{{ $row['balance']?->notes ?: '-' }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>

    <div class="card">
        <h2>Financial Summary</h2>
        <div class="stats-grid">
            <div class="stat-card"><p>Total USD Available</p><h2>USD {{ number_format($summary['total_available_usd'], 2) }}</h2></div>
            <div class="stat-card"><p>Monthly Facebook Spend</p><h2>USD {{ number_format($summary['monthly_facebook_spend'], 2) }}</h2></div>
            <div class="stat-card"><p>Monthly Card Fees</p><h2>USD {{ number_format($summary['monthly_card_fees'], 2) }}</h2></div>
            <div class="stat-card"><p>Monthly Extra Charges</p><h2>USD {{ number_format($summary['monthly_extra_charges'], 2) }}</h2></div>
            <div class="stat-card"><p>Total Deducted USD</p><h2>USD {{ number_format($summary['monthly_total_deducted'], 2) }}</h2></div>
            <div class="stat-card"><p>Monthly Revenue</p><h2>BDT {{ number_format($summary['monthly_revenue'], 2) }}</h2></div>
            <div class="stat-card"><p>Actual Cost</p><h2>BDT {{ number_format($summary['monthly_actual_cost'], 2) }}</h2></div>
            <div class="stat-card"><p>Estimated Profit</p><h2>BDT {{ number_format($summary['estimated_profit'], 2) }}</h2></div>
        </div>
    </div>

    <div class="card">
        <h2>Funding History</h2>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Source</th>
                    <th>Previous Balance</th>
                    <th>New Balance</th>
                    <th>Difference</th>
                    <th>Updated By</th>
                    <th>Note</th>
                    <th>Actions</th>
                </tr>
                @forelse($history as $item)
                    <tr>
                        <td>{{ $item->balance_date?->toDateString() }}</td>
                        <td>{{ $item->sourceLabel() }}</td>
                        <td>USD {{ number_format((float) $item->previous_balance, 2) }}</td>
                        <td>USD {{ number_format((float) $item->new_balance, 2) }}</td>
                        <td>USD {{ number_format((float) $item->difference, 2) }}</td>
                        <td>{{ $item->createdBy?->name ?: '-' }}</td>
                        <td>{{ $item->note ?: '-' }}</td>
                        <td><a class="btn" href="/admin/facebook-financial/funding-history/{{ $item->id }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8">No funding history found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
