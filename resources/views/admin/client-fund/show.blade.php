@extends('layouts.admin')

@section('content')
    <h1>Client Fund Details</h1>
    <p>{{ $client->company_name }}</p>

    <p>
        <a class="btn" href="/admin/client-fund">Back to Client Fund Dashboard</a>
        <a class="btn" href="/admin/clients/{{ $client->id }}">Client Profile</a>
    </p>

    <style>
        .client-fund-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(5, minmax(150px, 1fr));
            margin: 18px 0;
        }

        .client-fund-card {
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.16);
            border-radius: 10px;
            padding: 14px;
        }

        .client-fund-card p {
            color: #a9b7cf;
            font-size: 13px;
            margin: 0 0 8px;
        }

        .client-fund-card h2 {
            font-size: 20px;
            margin: 0;
        }

        .balance-positive {
            border-color: rgba(34, 197, 94, .5) !important;
            color: #86efac;
        }

        .balance-warning {
            border-color: rgba(245, 158, 11, .6) !important;
            color: #fcd34d;
        }

        .balance-critical {
            border-color: rgba(239, 68, 68, .65) !important;
            color: #fca5a5;
        }

        .ledger-wrap {
            overflow-x: auto;
        }

        .ledger-table {
            min-width: 900px;
        }

        .ledger-table th:nth-child(n+4),
        .ledger-table td:nth-child(n+4) {
            text-align: right;
        }

        .ledger-filter-form {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(5, minmax(140px, 1fr));
            align-items: end;
        }

        .ledger-filter-form label {
            display: grid;
            gap: 6px;
            color: #a9b7cf;
            font-size: 13px;
        }

        .ledger-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        @media (max-width: 900px) {
            .client-fund-grid {
                grid-template-columns: repeat(2, minmax(150px, 1fr));
            }

            .ledger-filter-form {
                grid-template-columns: repeat(2, minmax(140px, 1fr));
            }
        }

        @media (max-width: 560px) {
            .client-fund-grid {
                grid-template-columns: 1fr;
            }

            .ledger-filter-form {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="client-fund-grid">
        <div class="client-fund-card">
            <p>Total Fund Received</p>
            <h2>BDT {{ number_format($row['fund_received'], 2) }}</h2>
        </div>
        <div class="client-fund-card">
            <p>Total Salary Used</p>
            <h2>BDT {{ number_format($row['salary_used'], 2) }}</h2>
        </div>
        <div class="client-fund-card {{ $row['balance_class'] }}">
            <p>Available Balance</p>
            <h2>BDT {{ number_format($row['available_balance'], 2) }}</h2>
        </div>
        <div class="client-fund-card">
            <p>Pending Payments</p>
            <h2>BDT {{ number_format($row['pending_payments'], 2) }}</h2>
        </div>
        <div class="client-fund-card">
            <p>Unpaid Salary Due</p>
            <h2>BDT {{ number_format($row['unpaid_salary_due'], 2) }}</h2>
            <p style="margin-top:6px;">{{ number_format($row['unpaid_employee_count']) }} Employees</p>
        </div>
    </div>

    <div class="card">
        <h2>Transaction Ledger</h2>
        <form class="ledger-filter-form" method="GET" action="/admin/client-fund/{{ $client->id }}/details">
            <label>
                From Date
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </label>
            <label>
                To Date
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </label>
            <label>
                Transaction Type
                <select name="type">
                    <option value="">All Transactions</option>
                    @foreach(['Client Fund Received', 'Employee Salary Paid'] as $type)
                        <option value="{{ $type }}" {{ ($filters['type'] ?? '') === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </label>
            <div class="ledger-actions">
                <button class="btn" type="submit">Filter</button>
                <a href="/admin/client-fund/{{ $client->id }}/details">Reset</a>
            </div>
            <div class="ledger-actions">
                <a class="btn" href="/admin/client-fund/{{ $client->id }}/details/export/csv?{{ http_build_query($filters ?? []) }}">Export CSV</a>
                <a class="btn" href="/admin/client-fund/{{ $client->id }}/details/export/excel?{{ http_build_query($filters ?? []) }}">Export Excel</a>
            </div>
        </form>

        <div class="ledger-wrap">
            <table class="ledger-table">
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Credit</th>
                    <th>Debit</th>
                    <th>Running Balance</th>
                </tr>
                @forelse($ledger as $entry)
                    <tr>
                        <td>{{ $entry['date'] }}</td>
                        <td>{{ $entry['type'] }}</td>
                        <td>{{ $entry['description'] }}</td>
                        <td>{{ $entry['credit'] > 0 ? 'BDT ' . number_format($entry['credit'], 2) : '-' }}</td>
                        <td>{{ $entry['debit'] > 0 ? 'BDT ' . number_format($entry['debit'], 2) : '-' }}</td>
                        <td>BDT {{ number_format($entry['running_balance'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No client fund transactions found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
