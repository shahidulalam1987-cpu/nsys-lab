@extends('layouts.admin')

@section('content')
    <style>
        .billing-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
        }

        .billing-header p {
            max-width: 760px;
        }

        .billing-alert {
            border: 1px solid rgba(56, 189, 248, .35);
            background: rgba(14, 165, 233, .09);
            color: #bae6fd;
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 18px;
        }

        .billing-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 18px;
        }

        .billing-form-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            align-items: end;
        }

        .billing-form-grid label {
            display: grid;
            gap: 8px;
            font-weight: 800;
        }

        .billing-form-grid input,
        .billing-form-grid select {
            width: 100%;
            min-width: 0;
        }

        .billing-form-grid .wide {
            grid-column: span 2;
        }

        .billing-table-account {
            color: #e5f2ff;
            font-weight: 800;
            text-decoration: none;
        }

        .billing-table-account:hover {
            color: #38d9ff;
            text-decoration: underline;
        }

        .billing-muted {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 4px;
        }

        @media (max-width: 1100px) {
            .billing-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .billing-header {
                display: block;
            }

            .billing-grid,
            .billing-form-grid {
                grid-template-columns: 1fr;
            }

            .billing-form-grid .wide {
                grid-column: span 1;
            }
        }
    </style>

    <div class="billing-header">
        <div>
            <h1>Billing History</h1>
            <p>Track ad account billing events for audit and reconciliation.</p>
        </div>
    </div>

    <div class="billing-alert">
        Billing History is audit-only. Adding a billing record here does not update finance accounts, ad account ledgers, card balances, or client funds.
    </div>

    <div class="billing-grid">
        <div class="stat-card">
            <p>Total Records</p>
            <h2>{{ number_format($summary['total_records']) }}</h2>
        </div>
        <div class="stat-card">
            <p>Total Billing</p>
            <h2>USD {{ number_format($summary['total_amount'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Paid Billing</p>
            <h2>USD {{ number_format($summary['paid_amount'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Pending / Overdue</p>
            <h2>{{ number_format($summary['pending_count']) }} / {{ number_format($summary['overdue_count']) }}</h2>
        </div>
    </div>

    <div class="card">
        <h2>Filters</h2>
        <form method="GET" action="/admin/ad-account-billing-history" class="billing-form-grid">
            <label>
                Ad Account
                <select name="ad_account_id">
                    <option value="">All Ad Accounts</option>
                    @foreach($adAccounts as $account)
                        <option value="{{ $account->id }}" @selected(($filters['ad_account_id'] ?? '') == $account->id)>{{ $account->ad_account_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Status
                <select name="payment_status">
                    <option value="">All Status</option>
                    <option value="pending" @selected(($filters['payment_status'] ?? '') === 'pending')>Pending</option>
                    <option value="paid" @selected(($filters['payment_status'] ?? '') === 'paid')>Paid</option>
                    <option value="overdue" @selected(($filters['payment_status'] ?? '') === 'overdue')>Overdue</option>
                </select>
            </label>
            <label>
                From Date
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </label>
            <label>
                To Date
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </label>
            <div>
                <button class="btn" type="submit">Filter</button>
                <a href="/admin/ad-account-billing-history" style="margin-left:12px;">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Add Billing Record</h2>
        <p class="billing-muted">Use this for external billing references and reconciliation notes only.</p>
        <form method="POST" action="/admin/ad-account-billing-history" class="billing-form-grid">
            @csrf
            <label>
                Ad Account
                <select name="ad_account_id" required>
                    @foreach($adAccounts as $account)
                        <option value="{{ $account->id }}" @selected(old('ad_account_id') == $account->id)>{{ $account->ad_account_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Billing Date
                <input type="date" name="billing_date" value="{{ old('billing_date') }}" required>
            </label>
            <label>
                Amount USD
                <input type="number" step="0.01" name="billing_amount_usd" value="{{ old('billing_amount_usd') }}" required>
            </label>
            <label>
                Paid Date
                <input type="date" name="paid_date" value="{{ old('paid_date') }}">
            </label>
            <label>
                Status
                <select name="payment_status">
                    <option value="pending" @selected(old('payment_status', 'pending') === 'pending')>Pending</option>
                    <option value="paid" @selected(old('payment_status') === 'paid')>Paid</option>
                    <option value="overdue" @selected(old('payment_status') === 'overdue')>Overdue</option>
                </select>
            </label>
            <label>
                Reference
                <input name="reference" value="{{ old('reference') }}">
            </label>
            <label class="wide">
                Notes
                <input name="notes" value="{{ old('notes') }}">
            </label>
            <button class="btn" type="submit">Add Billing</button>
        </form>
    </div>

    <div class="card table-wrap">
        <h2>Billing Records</h2>
        <table>
            <tr>
                <th>Billing Date</th>
                <th>Ad Account</th>
                <th>Amount</th>
                <th>Paid Date</th>
                <th>Status</th>
                <th>Reference</th>
            </tr>
            @forelse($history as $row)
                @php
                    $statusClass = [
                        'paid' => 'badge-success',
                        'pending' => 'badge-warning',
                        'overdue' => 'badge-danger',
                    ][$row->payment_status] ?? 'badge-neutral';
                @endphp
                <tr>
                    <td>{{ $row->billing_date?->toDateString() }}</td>
                    <td>
                        @if($row->adAccount)
                            <a class="billing-table-account" href="/admin/ad-accounts/{{ $row->adAccount->id }}">{{ $row->adAccount->ad_account_name }}</a>
                            <div class="billing-muted">{{ $row->adAccount->ad_account_id }}</div>
                        @else
                            -
                        @endif
                    </td>
                    <td>USD {{ number_format((float) $row->billing_amount_usd, 2) }}</td>
                    <td>{{ $row->paid_date?->toDateString() ?: '-' }}</td>
                    <td><span class="badge {{ $statusClass }}">{{ ucfirst($row->payment_status) }}</span></td>
                    <td>{{ $row->reference ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No billing history found.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
