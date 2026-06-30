@extends('layouts.employee')

@section('content')
    @php($isOrder = $type === 'order')
    <h1>{{ $isOrder ? 'Daily Orders' : 'Daily Spend' }}</h1>
    <p style="color:var(--muted);">Submit page performance for admin review. Only your active assignment scope is available.</p>

    <div class="stats-grid">
        <div class="stat-card"><p>Today</p><h2>{{ $submissions->filter(fn ($submission) => $submission->submission_date?->isToday())->count() }}</h2></div>
        <div class="stat-card"><p>Pending</p><h2>{{ $submissions->where('status', 'pending')->count() }}</h2></div>
        <div class="stat-card"><p>Approved</p><h2>{{ $submissions->whereIn('status', ['approved', 'merged'])->count() }}</h2></div>
        <div class="stat-card"><p>Rejected</p><h2>{{ $submissions->where('status', 'rejected')->count() }}</h2></div>
    </div>

    <div class="card">
        <h2>New {{ $isOrder ? 'Order' : 'Spend' }} Submission</h2>
        @if($scope['pages']->isEmpty())
            <p style="color:var(--warning);">No active assigned page is available for this date.</p>
        @else
            <form method="POST" action="{{ $isOrder ? '/employee/daily-orders' : '/employee/daily-spend' }}" enctype="multipart/form-data">
                @csrf
                <p>Date<br><input type="date" name="submission_date" value="{{ old('submission_date', $date->toDateString()) }}" max="{{ today()->toDateString() }}" required></p>
                <p>Page<br>
                    <select name="page_id" id="submission_page" required>
                        <option value="">Select Page</option>
                        @foreach($scope['pages'] as $page)
                            <option value="{{ $page->id }}" data-client="{{ $page->client?->company_name }}" {{ (int) old('page_id') === $page->id ? 'selected' : '' }}>{{ $page->page_name }} - {{ $page->client?->company_name }}</option>
                        @endforeach
                    </select>
                </p>
                <p>Client<br><input id="submission_client" type="text" readonly value=""></p>
                <p>Campaign{{ $isOrder ? ' (Optional)' : '' }}<br>
                    <select name="campaign_id" id="submission_campaign" {{ $isOrder ? '' : 'required' }}>
                        <option value="">{{ $isOrder ? 'No Campaign' : 'Select Campaign' }}</option>
                        @foreach($scope['campaigns'] as $campaign)
                            <option value="{{ $campaign->id }}" data-page-id="{{ $campaign->client_page_id }}" data-bm="{{ $campaign->businessManager?->bm_name }}" data-account="{{ $campaign->adAccount?->ad_account_name }}" {{ (int) old('campaign_id') === $campaign->id ? 'selected' : '' }}>{{ $campaign->campaign_name }} (#{{ $campaign->campaign_id }})</option>
                        @endforeach
                    </select>
                </p>

                @if($isOrder)
                    <p>Total Orders<br><input type="number" name="orders" min="0" value="{{ old('orders', 0) }}" required></p>
                    <p>Confirmed Orders<br><input type="number" name="confirmed_orders" min="0" value="{{ old('confirmed_orders', 0) }}"></p>
                    <p>Cancelled Orders<br><input type="number" name="cancelled_orders" min="0" value="{{ old('cancelled_orders', 0) }}"></p>
                @else
                    <p>BM<br><input id="submission_bm" type="text" readonly></p>
                    <p>Ad Account<br><input id="submission_account" type="text" readonly></p>
                    <p>Dollar Spend (USD)<br><input type="number" name="dollar_spend" min="0" step="0.01" value="{{ old('dollar_spend', 0) }}" required></p>
                    <p>CPM<br><input type="number" name="cpm" min="0" step="0.01" value="{{ old('cpm') }}"></p>
                    <p>CPC<br><input type="number" name="cpc" min="0" step="0.01" value="{{ old('cpc') }}"></p>
                    <p>CTR<br><input type="number" name="ctr" min="0" step="0.0001" value="{{ old('ctr') }}"></p>
                    <p>Screenshot<br><input type="file" name="screenshot" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></p>
                @endif
                <p>Note<br><textarea name="note">{{ old('note') }}</textarea></p>
                <button class="btn" type="submit">Submit for Review</button>
            </form>
        @endif
    </div>

    <div class="card table-wrap">
        <h2>My Submissions</h2>
        <table>
            <thead><tr><th>Date</th><th>Page</th><th>Campaign</th><th>{{ $isOrder ? 'Orders' : 'Spend' }}</th><th>Status</th><th>Employee Note</th><th>Admin Note</th></tr></thead>
            <tbody>
                @forelse($submissions as $submission)
                    <tr>
                        <td>{{ $submission->submission_date?->toDateString() }}</td>
                        <td>{{ $submission->page?->page_name ?: '-' }}</td>
                        <td>{{ $submission->campaign?->campaign_name ?: '-' }}</td>
                        <td>{{ $isOrder ? number_format($submission->orders ?? 0) : 'USD ' . number_format($submission->dollar_spend ?? 0, 2) }}</td>
                        <td><span class="badge {{ in_array($submission->status, ['approved', 'merged']) ? 'badge-success' : ($submission->status === 'rejected' ? 'badge-danger' : 'badge-warning') }}">{{ $submission->statusLabel() }}</span></td>
                        <td>{{ $submission->note ?: '-' }}</td>
                        <td>{{ $submission->admin_note ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">No submissions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
        const pageSelect = document.getElementById('submission_page');
        const campaignSelect = document.getElementById('submission_campaign');
        const clientField = document.getElementById('submission_client');
        const bmField = document.getElementById('submission_bm');
        const accountField = document.getElementById('submission_account');
        const campaignOptions = campaignSelect ? Array.from(campaignSelect.options).slice(1) : [];
        const refreshScope = () => {
            const pageId = pageSelect?.value || '';
            if (clientField) clientField.value = pageSelect?.selectedOptions[0]?.dataset.client || '';
            campaignOptions.forEach(option => option.hidden = option.dataset.pageId !== pageId);
            if (campaignSelect?.selectedOptions[0]?.hidden) campaignSelect.value = '';
            const selected = campaignSelect?.selectedOptions[0];
            if (bmField) bmField.value = selected?.dataset.bm || '';
            if (accountField) accountField.value = selected?.dataset.account || '';
        };
        pageSelect?.addEventListener('change', refreshScope);
        campaignSelect?.addEventListener('change', refreshScope);
        refreshScope();
    </script>
@endsection
