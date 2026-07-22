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
                <label>Client<br>
                    <select id="performance-client">
                        <option value="">Select Client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" @selected(old('client_id', $dailyReport?->campaign?->client_id) == $client->id)>{{ $client->company_name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Page<br>
                    <select id="performance-page">
                        <option value="">Select Page</option>
                        @foreach($clientPages as $page)
                            <option value="{{ $page->id }}" data-client-id="{{ $page->client_id }}" @selected(old('client_page_id', $dailyReport?->campaign?->client_page_id) == $page->id)>{{ $page->page_name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Campaign<br>
                    <select name="campaign_id" id="performance-campaign" required>
                        <option value="">Select Campaign</option>
                        @foreach($campaigns as $campaign)
                            <option value="{{ $campaign->id }}"
                                data-client-id="{{ $campaign->client_id }}"
                                data-page-id="{{ $campaign->client_page_id }}"
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
                <label>Direct Campaign ID Search<br>
                    <input type="text" id="campaign-id-search" list="campaign-id-options" placeholder="Type Campaign ID">
                    <datalist id="campaign-id-options">
                        @foreach($campaigns as $campaign)
                            <option value="{{ $campaign->campaign_id }}">{{ $campaign->campaign_name }}</option>
                        @endforeach
                    </datalist>
                </label>
                <label>Campaign ID<br><input type="text" id="campaign-id-readonly" readonly></label>
                <label>BM<br><input type="text" id="campaign-bm-readonly" readonly></label>
                <label>Ad Account<br><input type="text" id="campaign-ad-account-readonly" readonly></label>
                <label>Spend (USD)<br><input type="number" step="0.01" min="0" name="spend" value="{{ old('spend', $dailyReport?->spend ?? 0) }}" required></label>
                <label>Orders<br><input type="number" min="0" name="orders" value="{{ old('orders', $dailyReport?->orders ?? 0) }}" required></label>
                <label>Card Provider<br>
                    <select name="card_provider">
                        <option value="">Select Provider</option>
                        @foreach(['RedotPay', 'Tevau', 'Other'] as $provider)
                            <option value="{{ $provider }}" @selected(old('card_provider', $dailyReport?->card_provider) === $provider)>{{ $provider }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Fee USD<br><input type="number" step="0.01" min="0" name="fee_usd" value="{{ old('fee_usd', $dailyReport?->fee_usd ?? 0) }}"></label>
                <label>Extra Charge USD<br><input type="number" step="0.01" min="0" name="extra_charge_usd" value="{{ old('extra_charge_usd', $dailyReport?->extra_charge_usd ?? 0) }}"></label>
            </div>
            <details style="margin-top:12px;">
                <summary style="cursor:pointer;color:var(--cyan);font-weight:700;">Advanced Performance Fields</summary>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-top:12px;">
                    <label>Messages<br><input type="number" min="0" name="messages" value="{{ old('messages', $dailyReport?->messages ?? 0) }}"></label>
                    <label>Results<br><input type="number" min="0" name="results" value="{{ old('results', $dailyReport?->results ?? 0) }}"></label>
                    <label>Leads<br><input type="number" min="0" name="leads" value="{{ old('leads', $dailyReport?->leads ?? 0) }}"></label>
                    <label>Reach<br><input type="number" min="0" name="reach" value="{{ old('reach', $dailyReport?->reach ?? 0) }}"></label>
                    <label>Impressions<br><input type="number" min="0" name="impressions" value="{{ old('impressions', $dailyReport?->impressions ?? 0) }}"></label>
                    <label>Clicks<br><input type="number" min="0" name="clicks" value="{{ old('clicks', $dailyReport?->clicks ?? 0) }}"></label>
                </div>
            </details>
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
                            <th>Orders</th>
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
                                <td><input type="number" min="0" name="bulk_rows[{{ $index }}][orders]" value="0" style="width:80px;"></td>
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
                <p><strong>Campaign ID:</strong> <span data-campaign-summary="campaignId">-</span></p>
            </div>
        </div>

        <button class="btn" type="submit">{{ $button }}</button>
    </form>
</div>

<script>
    const entryMode = document.getElementById('performance-entry-mode');
    const singleForm = document.getElementById('single-performance-form');
    const bulkForm = document.getElementById('bulk-performance-form');
    const clientSelect = document.getElementById('performance-client');
    const pageSelect = document.getElementById('performance-page');
    const campaignSelect = document.getElementById('performance-campaign');
    const campaignSearch = document.getElementById('campaign-id-search');
    const campaignIdReadonly = document.getElementById('campaign-id-readonly');
    const campaignBmReadonly = document.getElementById('campaign-bm-readonly');
    const campaignAdAccountReadonly = document.getElementById('campaign-ad-account-readonly');

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
        campaignIdReadonly.value = data.id || '';
        campaignBmReadonly.value = data.bm || '';
        campaignAdAccountReadonly.value = data.adAccount || '';
        document.querySelector('[data-campaign-summary="name"]').textContent = data.name || '-';
        document.querySelector('[data-campaign-summary="status"]').textContent = data.status || '-';
        document.querySelector('[data-campaign-summary="bm"]').textContent = data.bm || '-';
        document.querySelector('[data-campaign-summary="adAccount"]').textContent = data.adAccount || '-';
        document.querySelector('[data-campaign-summary="client"]').textContent = data.client || '-';
        document.querySelector('[data-campaign-summary="page"]').textContent = data.page || '-';
        document.querySelector('[data-campaign-summary="objective"]').textContent = data.objective || '-';
        document.querySelector('[data-campaign-summary="campaignId"]').textContent = data.id || '-';
        campaignSearch.value = data.id || campaignSearch.value;
    }

    function filterPagesAndCampaigns() {
        const clientId = clientSelect?.value || '';
        const pageId = pageSelect?.value || '';

        pageSelect?.querySelectorAll('option[data-client-id]').forEach((option) => {
            option.hidden = clientId && option.dataset.clientId !== clientId;
        });

        if (pageSelect?.selectedOptions[0]?.hidden) {
            pageSelect.value = '';
        }

        campaignSelect?.querySelectorAll('option[data-client-id]').forEach((option) => {
            const clientMatches = !clientId || option.dataset.clientId === clientId;
            const pageMatches = !pageSelect?.value || option.dataset.pageId === pageSelect.value;
            option.hidden = !(clientMatches && pageMatches);
        });

        if (campaignSelect?.selectedOptions[0]?.hidden) {
            campaignSelect.value = '';
        }

        syncCampaignSummary();
    }

    function syncCampaignFromSearch() {
        const searchValue = (campaignSearch?.value || '').trim();
        if (!searchValue || !campaignSelect) return;

        const match = Array.from(campaignSelect.options).find((option) => option.dataset.id === searchValue);
        if (!match) return;

        campaignSelect.value = match.value;
        if (clientSelect) clientSelect.value = match.dataset.clientId || '';
        filterPagesAndCampaigns();
        if (pageSelect) pageSelect.value = match.dataset.pageId || '';
        filterPagesAndCampaigns();
    }

    entryMode?.addEventListener('change', syncEntryMode);
    clientSelect?.addEventListener('change', filterPagesAndCampaigns);
    pageSelect?.addEventListener('change', filterPagesAndCampaigns);
    campaignSelect?.addEventListener('change', syncCampaignSummary);
    campaignSearch?.addEventListener('change', syncCampaignFromSearch);
    syncEntryMode();
    filterPagesAndCampaigns();
    syncCampaignSummary();
</script>
