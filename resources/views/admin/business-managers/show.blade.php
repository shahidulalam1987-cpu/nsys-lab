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

    <div class="stats-grid">
        <div class="stat-card"><p>Ad Accounts</p><h2>{{ number_format($businessManager->adAccounts->count()) }}</h2></div>
        <div class="stat-card"><p>Pages</p><h2>{{ number_format($businessManager->pages->count()) }}</h2></div>
        <div class="stat-card"><p>Status</p><h2>{{ $businessManager->statusLabel() }}</h2></div>
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

    <div class="card">
        <h2>Linked Pages</h2>
        <div class="table-wrap">
            <table>
                <tr><th>Page</th><th>Client</th><th>Platform</th><th>Status</th></tr>
                @forelse($businessManager->pages as $page)
                    <tr>
                        <td>
                            @if($page->page_url)
                                <a href="{{ $page->page_url }}" target="_blank" rel="noopener">{{ $page->page_name }}</a>
                            @else
                                {{ $page->page_name }}
                            @endif
                            <br><span style="color:var(--muted);">{{ $page->page_id ?: '-' }}</span>
                        </td>
                        <td>{{ $page->client?->company_name ?: '-' }}</td>
                        <td>{{ $page->platform ?: '-' }}</td>
                        <td>{{ ucfirst((string) $page->status) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No pages linked.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
@endsection
