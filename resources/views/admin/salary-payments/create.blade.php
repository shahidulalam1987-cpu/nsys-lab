@extends('layouts.admin')

@section('content')
    <h1>Receive Client Payment</h1>

    <a class="btn" href="/admin/salary-payments">Client Payment History</a>
    <a class="btn" href="/admin/salary-payments/pending">Pending Client Payments</a>

    @if ($errors->any())
        <div class="card" style="color:#ef4444; margin-top:20px;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="margin-top:20px;">
        @if($financeAccounts->isEmpty())
            <div class="card" style="margin-bottom:18px; border-color:rgba(245,158,11,.55); background:rgba(245,158,11,.08);">
                <strong>No active BDT receiving account found.</strong>
                <p style="margin:8px 0 0;color:#cbd5e1;">
                    Add or activate a BDT Finance Account first. Client payments are recorded into an NSYS receiving account so the finance ledger can stay balanced.
                </p>
                <p style="margin:12px 0 0;">
                    <a class="btn" href="/admin/finance/accounts">Open Finance Accounts</a>
                </p>
            </div>
        @endif

        <form method="POST" action="/admin/salary-payments" enctype="multipart/form-data">
            @csrf

            <p>Client<br>
                <select name="client_id" required>
                    <option value="">Select Client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>{{ $client->company_name }}</option>
                    @endforeach
                </select>
            </p>

            <p>Payment Purpose<br>
                <select name="fund_type" required>
                    <option value="employee_salary" {{ old('fund_type', 'employee_salary') === 'employee_salary' ? 'selected' : '' }}>Employee Salary Fund</option>
                    <option value="facebook_ads" {{ old('fund_type') === 'facebook_ads' ? 'selected' : '' }}>Facebook Ads Fund</option>
                </select>
            </p>

            <p>Amount (BDT)<br><input type="number" step="0.01" min="1" name="amount" value="{{ old('amount') }}" required></p>
            <p>NSYS Receiving Account (BDT)<br>
                <select name="finance_account_id" required @disabled($financeAccounts->isEmpty())>
                    <option value="">Select BDT Account</option>
                    @foreach($financeAccounts as $account)
                        <option value="{{ $account->id }}" @selected((string) old('finance_account_id') === (string) $account->id)>{{ $account->account_name }} - BDT {{ number_format((float) $account->current_balance, 2) }}</option>
                    @endforeach
                </select>
                <br><small style="color:#94a3b8;">Select the NSYS bank, cash, or wallet account where this client payment was received.</small>
            </p>

            <p>Payment Method<br>
                <select name="payment_method" required>
                    @foreach(['bKash', 'Nagad', 'Rocket', 'Bank', 'Cash'] as $method)
                        <option value="{{ $method }}" {{ old('payment_method') == $method ? 'selected' : '' }}>{{ $method }}</option>
                    @endforeach
                </select>
            </p>

            <p>Transaction ID / Reference<br><input type="text" name="transaction_id" value="{{ old('transaction_id') }}" required></p>
            <p>Payment Date<br><input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required></p>
            <p>Payment Proof<br><input type="file" name="screenshot" accept="image/*"></p>
            <p>Note<br><textarea name="note">{{ old('note') }}</textarea></p>
            <p>Status<br>
                <select name="status" required>
                    <option value="approved" {{ old('status', 'approved') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </p>

            <button class="btn" type="submit">Save Payment</button>
        </form>
    </div>
@endsection
