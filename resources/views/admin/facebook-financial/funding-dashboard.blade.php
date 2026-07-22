@extends('layouts.admin')

@section('content')
    <style>
        .funding-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
        }

        .funding-header p {
            max-width: 760px;
        }

        .funding-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .funding-note {
            border: 1px solid rgba(56, 189, 248, .35);
            background: rgba(14, 165, 233, .09);
            color: #bae6fd;
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 18px;
        }

        .funding-source-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .funding-source-card {
            border: 1px solid var(--line);
            border-radius: 12px;
            background: rgba(255,255,255,.06);
            padding: 16px;
        }

        .funding-source-card h3 {
            margin-bottom: 8px;
        }

        .funding-source-card h2 {
            margin-bottom: 10px;
        }

        .funding-muted {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 4px;
        }

        .funding-difference-positive {
            color: #22c55e;
            font-weight: 800;
        }

        .funding-difference-negative {
            color: #ef4444;
            font-weight: 800;
        }

        @media (max-width: 1100px) {
            .funding-source-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 760px) {
            .funding-header {
                display: block;
            }

            .funding-actions {
                justify-content: flex-start;
                margin-top: 12px;
            }
        }
    </style>

    <div class="funding-header">
        <div>
            <h1>Funding Dashboard</h1>
            <p>Real-time USD funding visibility for Binance and payment card sources.</p>
        </div>
        <div class="funding-actions">
            <a class="btn" href="/admin/facebook-financial/funding-dashboard/update">Manual Update</a>
            <a class="btn" href="/admin/facebook-cards">Card Management</a>
            <a class="btn" href="/admin/facebook-financial/binance-purchases">Binance Purchases</a>
        </div>
    </div>

    <div class="funding-note">
        Manual funding balances are tracking snapshots and are recorded in funding history. Card balances and Facebook spend remain managed through Card Management and card transactions.
    </div>

    <div class="card">
        <h2>Funding Sources</h2>
        <div class="stats-grid">
            <div class="stat-card"><p>Binance Balance</p><h2>USD {{ number_format($summary['binance_balance'], 2) }}</h2></div>
            <div class="stat-card"><p>RedotPay Balance</p><h2>USD {{ number_format($summary['redotpay_balance'], 2) }}</h2></div>
            <div class="stat-card"><p>Tevau Balance</p><h2>USD {{ number_format($summary['tavao_balance'], 2) }}</h2></div>
            <div class="stat-card"><p>Total Available USD</p><h2>USD {{ number_format($summary['total_available_usd'], 2) }}</h2></div>
        </div>
    </div>

    <div class="card">
        <h2>Card Balances</h2>
        <div class="stats-grid">
            <div class="stat-card"><p>RedotPay Card Balance</p><h2>USD {{ number_format($summary['redotpay_card_balance'], 2) }}</h2></div>
            <div class="stat-card"><p>Tevau Card Balance</p><h2>USD {{ number_format($summary['tavao_card_balance'], 2) }}</h2></div>
            <div class="stat-card"><p>Total Card Balance</p><h2>USD {{ number_format($summary['total_card_balance'], 2) }}</h2></div>
        </div>
    </div>

    <div class="card">
        <h2>Low Balance Alerts</h2>
        <div class="funding-source-grid">
            @foreach($balanceRows as $row)
                <div class="funding-source-card">
                    <h3>{{ $row['label'] }}</h3>
                    <h2>USD {{ number_format($row['current_balance'], 2) }}</h2>
                    <span class="badge {{ $row['status_class'] }}">{{ $row['status'] }}</span>
                    <div class="funding-muted">Low balance threshold: USD {{ number_format($row['limit'], 2) }}</div>
                    <div class="funding-muted">Last updated: {{ $row['last_updated'] ? $row['last_updated']->format('Y-m-d h:i A') : '-' }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <h2>Monthly Performance Summary</h2>
        <div class="stats-grid">
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
        <h2>Funding Source Details</h2>
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
                    @php
                        $difference = (float) $item->difference;
                        $differenceClass = $difference >= 0 ? 'funding-difference-positive' : 'funding-difference-negative';
                    @endphp
                    <tr>
                        <td>{{ $item->balance_date?->toDateString() }}</td>
                        <td>{{ $item->sourceLabel() }}</td>
                        <td>USD {{ number_format((float) $item->previous_balance, 2) }}</td>
                        <td>USD {{ number_format((float) $item->new_balance, 2) }}</td>
                        <td><span class="{{ $differenceClass }}">{{ $difference >= 0 ? '+' : '-' }} USD {{ number_format(abs($difference), 2) }}</span></td>
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
