@extends('layouts.admin')

@section('content')
    <h1>Client Fund Dashboard</h1>
    <p>Track client salary fund received, employee salary usage, pending client payments, and unpaid salary due.</p>

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

        .client-fund-table-wrap {
            overflow-x: auto;
        }

        .client-fund-table {
            min-width: 1080px;
        }

        .client-fund-table th,
        .client-fund-table td {
            vertical-align: middle;
        }

        .client-fund-table th:not(:first-child),
        .client-fund-table td:not(:first-child) {
            text-align: right;
        }

        .client-fund-table th:last-child,
        .client-fund-table td:last-child {
            text-align: center;
        }

        @media (max-width: 1200px) {
            .client-fund-grid {
                grid-template-columns: repeat(3, minmax(150px, 1fr));
            }
        }

        @media (max-width: 700px) {
            .client-fund-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="client-fund-grid">
        <div class="client-fund-card">
            <p>Total Fund Received</p>
            <h2>BDT {{ number_format($summary['total_fund_received'], 2) }}</h2>
        </div>
        <div class="client-fund-card">
            <p>Total Salary Used</p>
            <h2>BDT {{ number_format($summary['total_salary_used'], 2) }}</h2>
        </div>
        <div class="client-fund-card">
            <p>Available Balance</p>
            <h2>BDT {{ number_format($summary['available_balance'], 2) }}</h2>
        </div>
        <div class="client-fund-card">
            <p>Pending Client Payments</p>
            <h2>BDT {{ number_format($summary['pending_client_payments'], 2) }}</h2>
            <p style="margin-top:6px;">{{ number_format($summary['pending_client_payment_count']) }} pending</p>
        </div>
        <div class="client-fund-card">
            <p>Unpaid Salary Due</p>
            <h2>BDT {{ number_format($summary['unpaid_salary_due'], 2) }}</h2>
        </div>
    </div>

    <div class="card">
        <h2>Client-wise Fund Summary</h2>
        <div class="client-fund-table-wrap">
            <table class="client-fund-table">
                <tr>
                    <th>Client</th>
                    <th>Total Fund Received</th>
                    <th>Salary Used</th>
                    <th>Available Balance</th>
                    <th>Pending Payments</th>
                    <th>Unpaid Salary Due</th>
                    <th>Action</th>
                </tr>
                @forelse($rows as $row)
                    <tr>
                        <td style="text-align:left;">{{ $row['client']->company_name }}</td>
                        <td>BDT {{ number_format($row['fund_received'], 2) }}</td>
                        <td>BDT {{ number_format($row['salary_used'], 2) }}</td>
                        <td>BDT {{ number_format($row['available_balance'], 2) }}</td>
                        <td>
                            BDT {{ number_format($row['pending_payments'], 2) }}
                            <div style="color:#a9b7cf; font-size:12px;">{{ number_format($row['pending_payment_count']) }} pending</div>
                        </td>
                        <td>BDT {{ number_format($row['unpaid_salary_due'], 2) }}</td>
                        <td><a class="btn" href="/admin/clients/{{ $row['client']->id }}">View Details</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7">No client fund data found.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
