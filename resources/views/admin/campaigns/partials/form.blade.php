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
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
            <label>Campaign Name<br><input type="text" name="campaign_name" value="{{ old('campaign_name', $campaign?->campaign_name) }}" required></label>
            <label>Campaign ID<br><input type="text" name="campaign_id" value="{{ old('campaign_id', $campaign?->campaign_id) }}" required></label>
            <label>BM<br>
                <select name="business_manager_id" id="campaign-bm" required>
                    <option value="">Select BM</option>
                    @foreach($businessManagers as $bm)
                        <option value="{{ $bm->id }}" @selected(old('business_manager_id', $campaign?->business_manager_id) == $bm->id)>{{ $bm->bm_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Ad Account<br>
                <select name="ad_account_id" id="campaign-ad-account" required>
                    <option value="">Select Ad Account</option>
                    @foreach($adAccounts as $account)
                        <option value="{{ $account->id }}" data-bm-id="{{ $account->business_manager_id }}" data-client-id="{{ $account->client_id }}" @selected(old('ad_account_id', $campaign?->ad_account_id) == $account->id)>
                            {{ $account->ad_account_name }} - {{ $account->ad_account_id }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label>Client<br>
                <select name="client_id" id="campaign-client" required>
                    <option value="">Select Client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" @selected(old('client_id', $campaign?->client_id) == $client->id)>{{ $client->company_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Page Search<br><input type="text" id="campaign-page-search" placeholder="Type page name"></label>
            <label>Page<br>
                <select name="client_page_id" id="campaign-page" required>
                    <option value="">Select Page</option>
                    @foreach($clientPages as $page)
                        <option value="{{ $page->id }}" data-client-id="{{ $page->client_id }}" data-bm-id="{{ $page->business_manager_id }}" data-ad-account-id="{{ $page->ad_account_id }}" data-page-name="{{ strtolower($page->page_name) }}" @selected(old('client_page_id', $campaign?->client_page_id) == $page->id)>
                            {{ $page->page_name }} ({{ $page->platform }}) - {{ $page->client?->company_name }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label>Objective<br>
                <select name="objective" required>
                    @foreach($objectives as $value => $label)
                        <option value="{{ $value }}" @selected(old('objective', $campaign?->objective ?? 'messages') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Status<br>
                <select name="status" required>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $campaign?->status ?? 'draft') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Start Date<br><input type="date" name="start_date" value="{{ old('start_date', $campaign?->start_date?->toDateString()) }}"></label>
            <label>End Date<br><input type="date" name="end_date" value="{{ old('end_date', $campaign?->end_date?->toDateString()) }}"></label>
            <label>Daily Budget (USD)<br><input type="number" step="0.01" min="0" name="daily_budget" value="{{ old('daily_budget', $campaign?->daily_budget ?? 0) }}"></label>
            <label>Lifetime Budget (USD)<br><input type="number" step="0.01" min="0" name="lifetime_budget" value="{{ old('lifetime_budget', $campaign?->lifetime_budget ?? 0) }}"></label>
        </div>
        <p>Notes<br><textarea name="notes">{{ old('notes', $campaign?->notes) }}</textarea></p>
        <button class="btn" type="submit">{{ $button }}</button>
    </form>
</div>

<script>
    const bmSelect = document.getElementById('campaign-bm');
    const adAccountSelect = document.getElementById('campaign-ad-account');
    const clientSelect = document.getElementById('campaign-client');
    const pageSelect = document.getElementById('campaign-page');
    const pageSearch = document.getElementById('campaign-page-search');

    function filterCampaignRelations() {
        const bmId = bmSelect.value;
        const adAccountOption = adAccountSelect.selectedOptions[0];
        const adAccountId = adAccountSelect.value;
        const adAccountClientId = adAccountOption?.dataset.clientId || '';
        const clientId = clientSelect.value;
        const pageTerm = pageSearch.value.trim().toLowerCase();

        adAccountSelect.querySelectorAll('option[data-bm-id]').forEach((option) => {
            option.hidden = bmId && option.dataset.bmId !== bmId;
        });
        if (adAccountSelect.selectedOptions[0]?.hidden) {
            adAccountSelect.value = '';
        }

        clientSelect.querySelectorAll('option[value]').forEach((option) => {
            if (! option.value) {
                return;
            }
            option.hidden = Boolean(adAccountClientId && option.value !== adAccountClientId);
        });
        if (clientSelect.selectedOptions[0]?.hidden) {
            clientSelect.value = adAccountClientId || '';
        }

        pageSelect.querySelectorAll('option[data-client-id]').forEach((option) => {
            const matchesClient = !clientId || option.dataset.clientId === clientId;
            const matchesBm = !bmId || !option.dataset.bmId || option.dataset.bmId === bmId;
            const matchesAd = !adAccountId || !option.dataset.adAccountId || option.dataset.adAccountId === adAccountId;
            const matchesTerm = !pageTerm || option.dataset.pageName.includes(pageTerm);
            option.hidden = !(matchesClient && matchesBm && matchesAd && matchesTerm);
        });
        if (pageSelect.selectedOptions[0]?.hidden) {
            pageSelect.value = '';
        }
    }

    bmSelect.addEventListener('change', filterCampaignRelations);
    adAccountSelect.addEventListener('change', filterCampaignRelations);
    clientSelect.addEventListener('change', filterCampaignRelations);
    pageSearch.addEventListener('input', filterCampaignRelations);
    filterCampaignRelations();
</script>
