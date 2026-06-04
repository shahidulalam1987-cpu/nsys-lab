@extends('layouts.client')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:16px; align-items:flex-start; flex-wrap:wrap; margin-bottom:18px;">
        <div>
            <h1 style="margin:0;">Statement</h1>
            <p style="margin:8px 0 0;">{{ $client->company_name }} ledger</p>
        </div>
        <a class="btn" href="/client/dashboard">Dashboard</a>
    </div>

    <div class="card">
        <form method="GET" action="/client/statement" style="display:flex; gap:12px; flex-wrap:wrap; align-items:end;">
            <div>
                <label>From Date</label><br>
                <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}">
            </div>

            <div>
                <label>To Date</label><br>
                <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}">
            </div>

            <button class="btn" type="submit">Filter</button>
            <a class="btn" href="/client/statement">Reset</a>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <p>Total Spend</p>
            <h2>BDT {{ number_format($totalSpend, 2) }}</h2>
        </div>

        <div class="stat-card">
            <p>Total Paid</p>
            <h2 style="color:#22c55e;">BDT {{ number_format($totalPaid, 2) }}</h2>
        </div>

        <div class="stat-card">
            <p>Current Due</p>
            <h2 style="color:#ef4444;">BDT {{ number_format($currentDue, 2) }}</h2>
        </div>

        <div class="stat-card">
            <p>Current Balance</p>
            <h2>
                @if($currentBalance >= 0)
                    BDT {{ number_format($currentBalance, 2) }}
                @else
                    -BDT {{ number_format(abs($currentBalance), 2) }}
                @endif
            </h2>
        </div>
    </div>

    <div class="card">
        <h2>Client Ledger</h2>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Transaction Type</th>
                    <th>Page</th>
                    <th>Debit</th>
                    <th>Credit</th>
                    <th>Running Balance</th>
                </tr>

                @forelse($ledgerRows as $row)
                    <tr>
                        <td>{{ $row['date'] }}</td>
                        <td>{{ $row['transaction_type'] }}</td>
                        <td>{{ $row['page'] }}</td>
                        <td>BDT {{ number_format($row['debit'], 2) }}</td>
                        <td>BDT {{ number_format($row['credit'], 2) }}</td>
                        <td>
                            @if($row['running_balance'] >= 0)
                                BDT {{ number_format($row['running_balance'], 2) }}
                            @else
                                -BDT {{ number_format(abs($row['running_balance']), 2) }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:32px;">No statement records found.</td>
                    </tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
