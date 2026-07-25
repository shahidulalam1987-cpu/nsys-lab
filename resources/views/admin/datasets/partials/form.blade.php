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
            <label>Name<br><input name="dataset_name" value="{{ old('dataset_name', $dataset?->dataset_name) }}" required></label>
            <label>Pixel / Dataset ID<br><input name="dataset_id" value="{{ old('dataset_id', $dataset?->dataset_id) }}" required></label>
            <label>Platform<br><input name="platform" value="{{ old('platform', $dataset?->platform ?? 'Meta') }}" required></label>
            <label>BM<br>
                <select name="business_manager_id" id="dataset-bm">
                    <option value="">None</option>
                    @foreach($businessManagers as $bm)
                        <option value="{{ $bm->id }}" @selected(old('business_manager_id', $dataset?->business_manager_id) == $bm->id)>{{ $bm->bm_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Ad Account<br>
                <select name="ad_account_id" id="dataset-ad-account">
                    <option value="">None</option>
                    @foreach($adAccounts as $account)
                        <option value="{{ $account->id }}" data-bm-id="{{ $account->business_manager_id }}" data-client-id="{{ $account->client_id }}" @selected(old('ad_account_id', $dataset?->ad_account_id) == $account->id)>{{ $account->ad_account_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Client<br>
                <select name="client_id" id="dataset-client">
                    <option value="">None</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" @selected(old('client_id', $dataset?->client_id) == $client->id)>{{ $client->company_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Page<br>
                <select name="client_page_id" id="dataset-page">
                    <option value="">None</option>
                    @foreach($pages as $page)
                        <option value="{{ $page->id }}" data-client-id="{{ $page->client_id }}" data-bm-id="{{ $page->business_manager_id }}" data-ad-account-id="{{ $page->ad_account_id }}" @selected(old('client_page_id', $dataset?->client_page_id) == $page->id)>{{ $page->page_name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Event Source<br>
                <select name="event_source_type" required>
                    @foreach($eventSourceTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('event_source_type', $dataset?->event_source_type ?? 'website') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Domain / Website URL<br><input name="domain_url" value="{{ old('domain_url', $dataset?->domain_url) }}" placeholder="https://example.com"></label>
            <label>Status<br>
                <select name="status" required>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $dataset?->status ?? 'active') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <p style="color:var(--muted);">Pixels and datasets are optional campaign tracking assets. They do not change spend, order, finance, or payroll calculations.</p>
        <p>Notes<br><textarea name="notes">{{ old('notes', $dataset?->notes) }}</textarea></p>
        <button class="btn" type="submit">{{ $button }}</button>
    </form>
</div>

<script>
    const bmSelect = document.getElementById('dataset-bm');
    const adAccountSelect = document.getElementById('dataset-ad-account');
    const clientSelect = document.getElementById('dataset-client');
    const pageSelect = document.getElementById('dataset-page');

    function filterDatasetRelations() {
        const bmId = bmSelect.value;
        const adAccountOption = adAccountSelect.selectedOptions[0];
        const adAccountId = adAccountSelect.value;
        const adAccountClientId = adAccountOption?.dataset.clientId || '';
        const clientId = clientSelect.value;

        adAccountSelect.querySelectorAll('option[data-bm-id]').forEach((option) => {
            option.hidden = bmId && option.dataset.bmId !== bmId;
        });
        if (adAccountSelect.selectedOptions[0]?.hidden) adAccountSelect.value = '';

        clientSelect.querySelectorAll('option[value]').forEach((option) => {
            if (!option.value) return;
            option.hidden = Boolean(adAccountClientId && option.value !== adAccountClientId);
        });
        if (clientSelect.selectedOptions[0]?.hidden) clientSelect.value = adAccountClientId || '';

        pageSelect.querySelectorAll('option[data-client-id]').forEach((option) => {
            const matchesClient = !clientId || option.dataset.clientId === clientId;
            const matchesBm = !bmId || !option.dataset.bmId || option.dataset.bmId === bmId;
            const matchesAd = !adAccountId || !option.dataset.adAccountId || option.dataset.adAccountId === adAccountId;
            option.hidden = !(matchesClient && matchesBm && matchesAd);
        });
        if (pageSelect.selectedOptions[0]?.hidden) pageSelect.value = '';
    }

    bmSelect.addEventListener('change', filterDatasetRelations);
    adAccountSelect.addEventListener('change', filterDatasetRelations);
    clientSelect.addEventListener('change', filterDatasetRelations);
    filterDatasetRelations();
</script>
