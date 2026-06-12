@extends('layouts.admin')

@section('content')
    <h1>Profit Dashboard</h1>
    <p>Actual profit analytics using Binance purchase rates, card fees, and client rates.</p>

    <div class="card">
        <form method="GET" action="/admin/facebook-financial/profit-dashboard">
            <label>Month<br><input type="month" name="month" value="{{ $month }}"></label>
            <button class="btn" type="submit">Filter</button>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Client Revenue</p><h2>BDT {{ number_format($summary['client_revenue'], 2) }}</h2></div>
        <div class="stat-card"><p>Facebook Spend</p><h2>USD {{ number_format($summary['facebook_spend'], 2) }}</h2></div>
        <div class="stat-card"><p>Card Fees</p><h2>USD {{ number_format($summary['card_fees'], 2) }}</h2></div>
        <div class="stat-card"><p>Actual BDT Cost</p><h2>BDT {{ number_format($summary['actual_bdt_cost'], 2) }}</h2></div>
        <div class="stat-card"><p>Net Profit</p><h2>BDT {{ number_format($summary['net_profit'], 2) }}</h2></div>
    </div>

    @foreach([
        'Card Wise' => $cardRows,
        'Client Wise' => $clientRows,
        'Ad Account Wise' => $adAccountRows,
        'Page Wise' => $pageRows,
        'Campaign Wise' => $campaignRows,
    ] as $title => $rows)
        <div class="card">
            <h2>{{ $title }}</h2>
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Name</th>
                        <th>Spend</th>
                        <th>Fees</th>
                        <th>Total Deducted</th>
                        @if($title === 'Card Wise')
                            <th>Balance</th>
                        @endif
                        <th>Cost</th>
                        <th>Revenue</th>
                        <th>Profit</th>
                    </tr>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td>USD {{ number_format($row['facebook_spend'], 2) }}</td>
                            <td>USD {{ number_format($row['card_fees'], 2) }}</td>
                            <td>USD {{ number_format($row['total_deducted'], 2) }}</td>
                            @if($title === 'Card Wise')
                                <td>USD {{ number_format($row['balance'], 2) }}</td>
                            @endif
                            <td>BDT {{ number_format($row['actual_bdt_cost'], 2) }}</td>
                            <td>BDT {{ number_format($row['client_revenue'], 2) }}</td>
                            <td>BDT {{ number_format($row['net_profit'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $title === 'Card Wise' ? 8 : 7 }}">No report data found.</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
    @endforeach
@endsection
