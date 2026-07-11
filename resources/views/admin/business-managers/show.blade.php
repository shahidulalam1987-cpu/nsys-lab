@extends('layouts.admin')

@section('content')
    <h1>Business Manager Details</h1>
    <a class="btn" href="/admin/business-managers">Back to Business Managers</a>
    <a class="btn" href="/admin/business-managers/{{ $businessManager->id }}/edit">Edit BM</a>

    <div class="card" style="margin-top:20px;">
        <h2>{{ $businessManager->bm_name }}</h2>
        <p><strong>BM ID:</strong> {{ $businessManager->bm_id }}</p>
        <p><strong>Owner:</strong> {{ $businessManager->owner_name }} ({{ $businessManager->owner_email }})</p>
        <p><strong>Verification:</strong> {{ $businessManager->verificationStatusLabel() }}</p>
        <p><strong>Status:</strong> {{ $businessManager->statusLabel() }}</p>
        <p><strong>Notes:</strong> {{ $businessManager->notes ?: '-' }}</p>
    </div>

    <div class="card">
        <h2>Linked Ad Accounts</h2>
        <div class="table-wrap">
            <table>
                <tr><th>Ad Account</th><th>Client</th><th>Status</th><th>Balance</th></tr>
                @forelse($businessManager->adAccounts as $account)
                    <tr>
                        <td><a href="/admin/ad-accounts/{{ $account->id }}">{{ $account->ad_account_name }}</a><br>{{ $account->ad_account_id }}</td>
                        <td>{{ $account->client?->company_name ?: '-' }}</td>
                        <td>{{ $account->statusLabel() }}</td>
                        <td>{{ $account->currency }} {{ number_format((float) $account->current_balance, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No ad accounts linked.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
