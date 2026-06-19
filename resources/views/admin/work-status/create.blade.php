@extends('layouts.admin')

@section('content')
    <h1>Add Work Status</h1>
    <a class="btn" href="/admin/work-status">Back to Work Status</a>

    <div class="card" style="margin-top:20px;">
        <form method="POST" action="/admin/work-status">
            @csrf
            <input type="hidden" name="return_to" value="{{ old('return_to', $prefill['return_to'] ?? '') }}">
            @php($entryMode = old('entry_mode', $prefill['entry_mode'] ?? 'single'))
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;align-items:end;">
                <p>Entry Mode<br>
                    <select name="entry_mode" id="work-status-entry-mode" required>
                        <option value="single" {{ $entryMode === 'single' ? 'selected' : '' }}>Single Date</option>
                        <option value="range" {{ $entryMode === 'range' ? 'selected' : '' }}>Date Range</option>
                    </select>
                </p>
                <p style="grid-column:span 2;color:var(--muted);margin:0 0 16px;">
                    Use Date Range when adding multiple work status records at once.
                </p>
            </div>

            <p>Employee<br>
                <select name="employee_id" id="work-status-employee" required>
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ old('employee_id', $prefill['employee_id'] ?? null) == $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }} ({{ $employee->employee_id }})
                        </option>
                    @endforeach
                </select>
            </p>
            <p>Client<br>
                <select name="client_id" class="js-client-select" data-page-target="work-status-page-create">
                    <option value="">No Client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_id', $prefill['client_id'] ?? null) == $client->id ? 'selected' : '' }}>{{ $client->company_name }}</option>
                    @endforeach
                </select>
            </p>
            <p>Page<br>
                <select id="work-status-page-create" name="client_page_id">
                    <option value="">No Page</option>
                    @foreach($clientPages as $page)
                        <option value="{{ $page->id }}" data-client-id="{{ $page->client_id }}" {{ old('client_page_id') == $page->id ? 'selected' : '' }}>
                            {{ $page->page_name }} ({{ $page->platform }})
                        </option>
                    @endforeach
                </select>
            </p>
            <p>Campaign<br>
                <select id="work-status-campaign-create" name="campaign_id">
                    <option value="">No Campaign</option>
                    @foreach($campaigns as $campaign)
                        <option value="{{ $campaign->id }}" data-client-id="{{ $campaign->client_id }}" data-page-id="{{ $campaign->client_page_id }}" {{ old('campaign_id') == $campaign->id ? 'selected' : '' }}>
                            {{ $campaign->campaign_name }} - {{ $campaign->campaign_id }}
                        </option>
                    @endforeach
                </select>
            </p>
            <p>Shift<br>
                <select name="shift_id">
                    <option value="">No Shift</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}" {{ old('shift_id') == $shift->id ? 'selected' : '' }}>{{ $shift->name }}: {{ $shift->timeRange() }}</option>
                    @endforeach
                </select>
            </p>
            <div id="single-date-fields">
                <p>Date<br><input type="date" name="work_date" value="{{ old('work_date', now()->toDateString()) }}"></p>
            </div>
            <div id="date-range-fields" style="display:none;">
                <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;">
                    <p>From Date<br><input type="date" name="from_date" value="{{ old('from_date', $prefill['from_date'] ?? '') }}"></p>
                    <p>To Date<br><input type="date" name="to_date" value="{{ old('to_date', $prefill['to_date'] ?? '') }}"></p>
                </div>
                <label style="display:flex;align-items:center;gap:8px;color:var(--muted);margin:4px 0 16px;">
                    <input type="checkbox" name="confirm_after_last_working_date" value="1" {{ old('confirm_after_last_working_date') ? 'checked' : '' }}>
                    Confirm override if this range includes dates after employee last working date.
                </label>
            </div>
            <p>Status<br>
                <select name="status" required>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ old('status', $prefill['status'] ?? 'working') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </p>
            <p>Note<br><textarea name="note">{{ old('note', $prefill['note'] ?? '') }}</textarea></p>
            <button class="btn" type="submit">Save Work Status</button>
        </form>
    </div>
    <script>
        const assignmentDefaults = @json($assignmentDefaults);
        const entryModeSelect = document.getElementById('work-status-entry-mode');
        const singleDateFields = document.getElementById('single-date-fields');
        const dateRangeFields = document.getElementById('date-range-fields');
        const workDateInput = document.querySelector('input[name="work_date"]');
        const fromDateInput = document.querySelector('input[name="from_date"]');
        const toDateInput = document.querySelector('input[name="to_date"]');

        const syncEntryMode = () => {
            const isRange = entryModeSelect.value === 'range';
            singleDateFields.style.display = isRange ? 'none' : '';
            dateRangeFields.style.display = isRange ? '' : 'none';
            workDateInput.required = ! isRange;
            fromDateInput.required = isRange;
            toDateInput.required = isRange;
        };

        entryModeSelect.addEventListener('change', syncEntryMode);
        syncEntryMode();

        document.querySelectorAll('.js-client-select').forEach((clientSelect) => {
            const pageSelect = document.getElementById(clientSelect.dataset.pageTarget);
            const campaignSelect = document.getElementById('work-status-campaign-create');
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

        document.getElementById('work-status-employee')?.addEventListener('change', (event) => {
            const defaults = assignmentDefaults[event.target.value] || {};
            const clientSelect = document.querySelector('select[name="client_id"]');
            const pageSelect = document.querySelector('select[name="client_page_id"]');
            const campaignSelect = document.querySelector('select[name="campaign_id"]');
            const shiftSelect = document.querySelector('select[name="shift_id"]');

            if (defaults.client_id) {
                clientSelect.value = defaults.client_id;
                clientSelect.dispatchEvent(new Event('change'));
            }

            if (defaults.client_page_id) {
                pageSelect.value = defaults.client_page_id;
            }

            if (defaults.campaign_id) {
                campaignSelect.value = defaults.campaign_id;
            }

            if (defaults.shift_id) {
                shiftSelect.value = defaults.shift_id;
            }
        });
    </script>
@endsection
