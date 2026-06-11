@if ($errors->any())
    <div class="card" style="color:#ef4444;margin-top:20px;">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="card" style="margin-top:20px;">
    <form method="POST" action="{{ $action }}">
        @csrf
        @if(! $isEdit)
            <p>Entry Mode<br>
                <select name="entry_mode" id="performance-entry-mode">
                    <option value="single">Single Entry</option>
                    <option value="bulk">Bulk Entry</option>
                </select>
            </p>
        @endif

        <div id="single-performance-form">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
                <label>Date<br><input type="date" name="report_date" value="{{ old('report_date', $dailyReport?->report_date?->toDateString() ?? now()->toDateString()) }}" required></label>
                <label>Campaign ID<br>
                    <select name="campaign_id" id="performance-campaign" required>
                        <option value="">Select Campaign</option>
                        @foreach($campaigns as $campaign)
                            <option value="{{ $campaign->id }}"
                                data-name="{{ $campaign->campaign_name }}"
                                data-id="{{ $campaign->campaign_id }}"
                                data-status="{{ $campaign->statusLabel() }}"
                                data-bm="{{ $campaign->businessManager?->bm_name }}"
                                data-ad-account="{{ $campaign->adAccount?->ad_account_name }}"
                                data-client="{{ $campaign->client?->company_name }}"
                                data-page="{{ $campaign->page?->page_name }}"
                                data-objective="{{ $campaign->objectiveLabel() }}"
                                @selected(old('campaign_id', $dailyReport?->campaign_id) == $campaign->id)>
                                {{ $campaign->campaign_id }} - {{ $campaign->campaign_name }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label>Spend (USD)<br><input type="number" step="0.01" min="0" name="spend" value="{{ old('spend', $dailyReport?->spend ?? 0) }}" required></label>
                <label>Messages<br><input type="number" min="0" name="messages" value="{{ old('messages', $dailyReport?->messages ?? 0) }}" required></label>
                <label>Results<br><input type="number" min="0" name="results" value="{{ old('results', $dailyReport?->results ?? 0) }}" required></label>
                <label>Leads<br><input type="number" min="0" name="leads" value="{{ old('leads', $dailyReport?->leads ?? 0) }}" required></label>
                <label>Orders<br><input type="number" min="0" name="orders" value="{{ old('orders', $dailyReport?->orders ?? 0) }}" required></label>
                <label>Reach<br><input type="number" min="0" name="reach" value="{{ old('reach', $dailyReport?->reach ?? 0) }}"></label>
                <label>Impressions<br><input type="number" min="0" name="impressions" value="{{ old('impressions', $dailyReport?->impressions ?? 0) }}"></label>
                <label>Clicks<br><input type="number" min="0" name="clicks" value="{{ old('clicks', $dailyReport?->clicks ?? 0) }}" required></label>
            </div>
            <label style="display:flex;align-items:center;gap:8px;margin-top:12px;color:var(--muted);">
                <input type="checkbox" name="update_existing" value="1">
                Update existing report if Campaign + Date already exists
            </label>
            <p>Notes<br><textarea name="notes">{{ old('notes', $dailyReport?->notes) }}</textarea></p>
        </div>

        @if(! $isEdit)
            <div id="bulk-performance-form" style="display:none;">
                <p>Bulk Date<br><input type="date" name="bulk_report_date" value="{{ old('bulk_report_date', now()->toDateString()) }}"></p>
                <p style="color:var(--muted);">Select multiple campaigns and enter quick performance values for the selected date.</p>
                <div class="table-wrap">
                    <table>
                        <tr>
                            <th>Use</th>
                            <th>Campaign</th>
                            <th>Spend</th>
                            <th>Messages</th>
                            <th>Results</th>
                            <th>Leads</th>
                            <th>Orders</th>
                            <th>Reach</th>
                            <th>Impressions</th>
                            <th>Clicks</th>
                            <th>Notes</th>
                        </tr>
                        @foreach($campaigns as $index => $campaign)
                            <tr>
                                <td><input type="checkbox" name="bulk_rows[{{ $index }}][enabled]" value="1"></td>
                                <td>
                                    {{ $campaign->campaign_id }}<br>{{ $campaign->campaign_name }}
                                    <input type="hidden" name="bulk_rows[{{ $index }}][campaign_id]" value="{{ $campaign->id }}">
                                </td>
                                <td><input type="number" step="0.01" min="0" name="bulk_rows[{{ $index }}][spend]" value="0" style="width:90px;"></td>
                                <td><input type="number" min="0" name="bulk_rows[{{ $index }}][messages]" value="0" style="width:80px;"></td>
                                <td><input type="number" min="0" name="bulk_rows[{{ $index }}][results]" value="0" style="width:80px;"></td>
                                <td><input type="number" min="0" name="bulk_rows[{{ $index }}][leads]" value="0" style="width:80px;"></td>
                                <td><input type="number" min="0" name="bulk_rows[{{ $index }}][orders]" value="0" style="width:80px;"></td>
                                <td><input type="number" min="0" name="bulk_rows[{{ $index }}][reach]" value="0" style="width:90px;"></td>
                                <td><input type="number" min="0" name="bulk_rows[{{ $index }}][impressions]" value="0" style="width:100px;"></td>
                                <td><input type="number" min="0" name="bulk_rows[{{ $index }}][clicks]" value="0" style="width:80px;"></td>
                                <td><input type="text" name="bulk_rows[{{ $index }}][notes]" style="width:160px;"></td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        @endif

        <div class="card" id="campaign-summary-card" style="background:rgba(15,23,42,.7);">
            <h2>Campaign Summary</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;">
                <p><strong>Campaign:</strong> <span data-campaign-summary="name">-</span></p>
                <p><strong>Status:</strong> <span data-campaign-summary="status">-</span></p>
                <p><strong>BM:</strong> <span data-campaign-summary="bm">-</span></p>
                <p><strong>Ad Account:</strong> <span data-campaign-summary="adAccount">-</span></p>
                <p><strong>Client:</strong> <span data-campaign-summary="client">-</span></p>
                <p><strong>Page:</strong> <span data-campaign-summary="page">-</span></p>
                <p><strong>Objective:</strong> <span data-campaign-summary="objective">-</span></p>
            </div>
        </div>

        <button class="btn" type="submit">{{ $button }}</button>
    </form>
</div>

<script>
    const entryMode = document.getElementById('performance-entry-mode');
    const singleForm = document.getElementById('single-performance-form');
    const bulkForm = document.getElementById('bulk-performance-form');
    const campaignSelect = document.getElementById('performance-campaign');

    function syncEntryMode() {
        if (!entryMode || !bulkForm) return;
        const isBulk = entryMode.value === 'bulk';
        singleForm.style.display = isBulk ? 'none' : '';
        bulkForm.style.display = isBulk ? '' : 'none';
        singleForm.querySelectorAll('input,select,textarea').forEach((field) => field.disabled = isBulk);
        bulkForm.querySelectorAll('input,select,textarea').forEach((field) => field.disabled = !isBulk);
    }

    function syncCampaignSummary() {
        if (!campaignSelect) return;
        const option = campaignSelect.selectedOptions[0];
        const data = option?.dataset || {};
        document.querySelector('[data-campaign-summary="name"]').textContent = data.name || '-';
        document.querySelector('[data-campaign-summary="status"]').textContent = data.status || '-';
        document.querySelector('[data-campaign-summary="bm"]').textContent = data.bm || '-';
        document.querySelector('[data-campaign-summary="adAccount"]').textContent = data.adAccount || '-';
        document.querySelector('[data-campaign-summary="client"]').textContent = data.client || '-';
        document.querySelector('[data-campaign-summary="page"]').textContent = data.page || '-';
        document.querySelector('[data-campaign-summary="objective"]').textContent = data.objective || '-';
    }

    entryMode?.addEventListener('change', syncEntryMode);
    campaignSelect?.addEventListener('change', syncCampaignSummary);
    syncEntryMode();
    syncCampaignSummary();
</script>
