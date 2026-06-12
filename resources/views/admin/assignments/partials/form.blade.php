@if ($errors->any())
    <div class="card" style="color:#ef4444; margin-top:20px;">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="card" style="margin-top:20px;">
    <form method="POST" action="{{ $action }}">
        @csrf

        <div class="assignment-form-grid" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px; align-items:end;">
            <p>Employee<br>
                <select name="employee_id" id="assignment-employee-select" required>
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" data-employee-type="{{ $employee->employee_type ?: 'client_assigned' }}" {{ old('employee_id', $assignment?->employee_id) == $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }} ({{ $employee->employee_id }})
                        </option>
                    @endforeach
                </select>
            </p>

            <p class="client-assignment-field">Client<br>
                <select name="client_id" class="js-client-select" data-page-target="assignment-page-select" required>
                    <option value="">Select Client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_id', $assignment?->client_id) == $client->id ? 'selected' : '' }}>
                            {{ $client->company_name }}
                        </option>
                    @endforeach
                </select>
            </p>

            <p class="client-assignment-field">Page Search<br>
                <input type="text" id="assignment-page-search" placeholder="Type page name">
            </p>

            <p class="client-assignment-field">Page<br>
                <select id="assignment-page-select" name="client_page_id" required>
                    <option value="">Select Page</option>
                    @foreach($clientPages as $page)
                        <option value="{{ $page->id }}" data-client-id="{{ $page->client_id }}" data-page-name="{{ strtolower($page->page_name) }}" {{ old('client_page_id', $assignment?->client_page_id) == $page->id ? 'selected' : '' }}>
                            {{ $page->page_name }} ({{ $page->platform }}) - {{ $page->client?->company_name }}
                        </option>
                    @endforeach
                </select>
            </p>

            <p class="client-assignment-field">Campaign<br>
                <select name="campaign_id" id="assignment-campaign-select">
                    <option value="">No Campaign</option>
                    @foreach($campaigns as $campaign)
                        <option value="{{ $campaign->id }}" data-client-id="{{ $campaign->client_id }}" data-page-id="{{ $campaign->client_page_id }}" {{ old('campaign_id', $assignment?->campaign_id) == $campaign->id ? 'selected' : '' }}>
                            {{ $campaign->campaign_name }} - {{ $campaign->campaign_id }}
                        </option>
                    @endforeach
                </select>
                @if(old('campaign', $assignment?->campaign))
                    <input type="hidden" name="campaign" value="{{ old('campaign', $assignment?->campaign) }}">
                @endif
            </p>

            <p>Shift<br>
                <select name="shift_id" required>
                    <option value="">Select Shift</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}" {{ old('shift_id', $assignment?->shift_id) == $shift->id ? 'selected' : '' }}>
                            {{ $shift->name }}: {{ $shift->timeRange() }}
                        </option>
                    @endforeach
                </select>
            </p>

            <p>Assigned From Date<br><input type="date" name="assigned_from" value="{{ old('assigned_from', $assignment?->assigned_from?->toDateString()) }}" required></p>
            <p>Assigned To Date<br><input type="date" name="assigned_to" value="{{ old('assigned_to', $assignment?->assigned_to?->toDateString()) }}"></p>

            <p>Status<br>
                <select name="status" required>
                    <option value="active" {{ old('status', $assignment?->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="ended" {{ old('status', $assignment?->status) === 'ended' ? 'selected' : '' }}>Inactive</option>
                </select>
            </p>
        </div>

        <p>Optional Notes<br><textarea name="note">{{ old('note', $assignment?->note) }}</textarea></p>
        <p id="internal-assignment-note" style="display:none;color:var(--muted);">Agency Internal employees do not require client/page/campaign assignment. Save only if you intentionally want to assign them to a client context.</p>

        <button class="btn" type="submit">{{ $button }}</button>
    </form>
</div>

<script>
    const clientSelect = document.querySelector('.js-client-select');
    const employeeSelect = document.getElementById('assignment-employee-select');
    const pageSelect = document.getElementById('assignment-page-select');
    const pageSearch = document.getElementById('assignment-page-search');
    const campaignSelect = document.getElementById('assignment-campaign-select');

    function filterAssignmentRelations() {
        const clientId = clientSelect.value;
        const pageId = pageSelect.value;
        const term = pageSearch.value.trim().toLowerCase();

        pageSelect.querySelectorAll('option[data-client-id]').forEach((option) => {
            const clientMatches = !clientId || option.dataset.clientId === clientId;
            const pageMatches = !term || option.dataset.pageName.includes(term);
            option.hidden = !(clientMatches && pageMatches);
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
    }

    clientSelect.addEventListener('change', filterAssignmentRelations);
    pageSelect.addEventListener('change', filterAssignmentRelations);
    pageSearch.addEventListener('input', filterAssignmentRelations);
    filterAssignmentRelations();

    function syncAssignmentEmployeeType() {
        const type = employeeSelect.selectedOptions[0]?.dataset.employeeType || 'client_assigned';
        const isInternal = type === 'agency_internal';
        document.querySelectorAll('.client-assignment-field').forEach((field) => {
            field.style.display = isInternal ? 'none' : '';
        });
        document.getElementById('internal-assignment-note').style.display = isInternal ? '' : 'none';
        clientSelect.required = ! isInternal;
        pageSelect.required = ! isInternal;
    }

    employeeSelect.addEventListener('change', syncAssignmentEmployeeType);
    syncAssignmentEmployeeType();
</script>
