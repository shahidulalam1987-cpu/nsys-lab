@extends('layouts.admin')

@section('content')
    <h1>Edit Work Status</h1>
    <a class="btn" href="/admin/work-status">Back to Work Status</a>

    <div class="card" style="margin-top:20px;">
        <h2>{{ $workStatus->employee?->name }} - {{ $workStatus->work_date?->toDateString() }}</h2>
        <form method="POST" action="/admin/work-status/{{ $workStatus->id }}/update">
            @csrf
            <p>Client<br>
                <select name="client_id" class="js-client-select" data-page-target="work-status-page-edit">
                    <option value="">No Client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_id', $workStatus->client_id) == $client->id ? 'selected' : '' }}>{{ $client->company_name }}</option>
                    @endforeach
                </select>
            </p>
            <p>Page<br>
                <select id="work-status-page-edit" name="client_page_id">
                    <option value="">No Page</option>
                    @foreach($clientPages as $page)
                        <option value="{{ $page->id }}" data-client-id="{{ $page->client_id }}" {{ old('client_page_id', $workStatus->client_page_id) == $page->id ? 'selected' : '' }}>
                            {{ $page->page_name }} ({{ $page->platform }})
                        </option>
                    @endforeach
                </select>
            </p>
            <p>Campaign<br>
                <select id="work-status-campaign-edit" name="campaign_id">
                    <option value="">No Campaign</option>
                    @foreach($campaigns as $campaign)
                        <option value="{{ $campaign->id }}" data-client-id="{{ $campaign->client_id }}" data-page-id="{{ $campaign->client_page_id }}" {{ old('campaign_id', $workStatus->campaign_id) == $campaign->id ? 'selected' : '' }}>
                            {{ $campaign->campaign_name }} - {{ $campaign->campaign_id }}
                        </option>
                    @endforeach
                </select>
            </p>
            <p>Shift<br>
                <select name="shift_id">
                    <option value="">No Shift</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}" {{ old('shift_id', $workStatus->shift_id) == $shift->id ? 'selected' : '' }}>{{ $shift->name }}: {{ $shift->timeRange() }}</option>
                    @endforeach
                </select>
            </p>
            <p>Status<br>
                <select name="status" required>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ old('status', $workStatus->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </p>
            <p>Salary Count Value<br>
                <input type="number" step="0.5" min="0" max="1" name="salary_count_value" value="{{ old('salary_count_value', $workStatus->salary_count_value) }}">
            </p>
            <p>Note<br><textarea name="note">{{ old('note', $workStatus->note) }}</textarea></p>
            <button class="btn" type="submit">Update Work Status</button>
        </form>
    </div>
    <script>
        document.querySelectorAll('.js-client-select').forEach((clientSelect) => {
            const pageSelect = document.getElementById(clientSelect.dataset.pageTarget);
            const campaignSelect = document.getElementById('work-status-campaign-edit');
            const filterRelations = () => {
                const clientId = clientSelect.value;
                const pageId = pageSelect.value;
                pageSelect.querySelectorAll('option[data-client-id]').forEach((option) => {
                    option.hidden = clientId && option.dataset.clientId !== clientId;
                });
                if (pageSelect.selectedOptions[0]?.hidden) {
                    pageSelect.value = '';
                }

                campaignSelect.querySelectorAll('option[data-client-id]').forEach((option) => {
                    const clientMatches = !clientId || option.dataset.clientId === clientId;
                    const pageMatches = !pageId || option.dataset.pageId === pageId;
                    option.hidden = !(clientMatches && pageMatches);
                });
                if (campaignSelect.selectedOptions[0]?.hidden) {
                    campaignSelect.value = '';
                }
            };
            clientSelect.addEventListener('change', filterRelations);
            pageSelect.addEventListener('change', filterRelations);
            filterRelations();
        });
    </script>
@endsection
