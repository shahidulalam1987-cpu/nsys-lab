@extends('layouts.admin')

@section('content')
    <style>
        .work-status-grid { display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px; }
        .work-status-grid p { margin:0; }
        .work-status-section { margin-top:18px;padding-top:18px;border-top:1px solid var(--line); }
        .assignment-message { margin:10px 0 0;color:var(--muted); }
        .assignment-message.warning { color:#fbbf24; }
        .preview-wrap { margin-top:18px;overflow-x:auto; }
        .preview-wrap table { min-width:920px; }
        .preview-wrap select { min-width:130px; }
        @media (max-width:900px) { .work-status-grid { grid-template-columns:1fr 1fr; } }
        @media (max-width:620px) { .work-status-grid { grid-template-columns:1fr; } }
    </style>

    <h1>Add Work Status</h1>
    <a class="btn" href="/admin/work-status">Back to Work Status</a>

    <div class="card" style="margin-top:20px;">
        <form method="POST" action="/admin/work-status" id="work-status-form">
            @csrf
            <input type="hidden" name="return_to" value="{{ old('return_to', $prefill['return_to'] ?? '') }}">
            @php($entryMode = old('entry_mode', $prefill['entry_mode'] ?? 'single'))

            <div class="work-status-grid">
                <p>Entry Mode<br>
                    <select name="entry_mode" id="work-status-entry-mode" required>
                        <option value="single" {{ $entryMode === 'single' ? 'selected' : '' }}>Single Date</option>
                        <option value="range" {{ $entryMode === 'range' ? 'selected' : '' }}>Date Range</option>
                        <option value="monthly" {{ $entryMode === 'monthly' ? 'selected' : '' }}>Monthly Cycle</option>
                    </select>
                </p>
                <p style="grid-column:span 2;color:var(--muted);align-self:end;margin-bottom:12px;">
                    Monthly Cycle prepares all salary-eligible dates and lets you review them before saving.
                </p>
            </div>

            <div class="work-status-section work-status-grid">
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
                    <select name="client_id" id="work-status-client">
                        <option value="">No Client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id', $prefill['client_id'] ?? null) == $client->id ? 'selected' : '' }}>{{ $client->company_name }}</option>
                        @endforeach
                    </select>
                </p>
                <p>Page<br>
                    <select id="work-status-page" name="client_page_id">
                        <option value="">No Page</option>
                        @foreach($clientPages as $page)
                            <option value="{{ $page->id }}" data-client-id="{{ $page->client_id }}" {{ old('client_page_id') == $page->id ? 'selected' : '' }}>
                                {{ $page->page_name }} ({{ $page->platform }})
                            </option>
                        @endforeach
                    </select>
                </p>
                <p>Campaign<br>
                    <select id="work-status-campaign" name="campaign_id">
                        <option value="">No Campaign</option>
                        @foreach($campaigns as $campaign)
                            <option value="{{ $campaign->id }}" data-client-id="{{ $campaign->client_id }}" data-page-id="{{ $campaign->client_page_id }}" {{ old('campaign_id') == $campaign->id ? 'selected' : '' }}>
                                {{ $campaign->campaign_name }} - {{ $campaign->campaign_id }}
                            </option>
                        @endforeach
                    </select>
                </p>
                <p>Shift<br>
                    <select name="shift_id" id="work-status-shift">
                        <option value="">No Shift</option>
                        @foreach($shifts as $shift)
                            <option value="{{ $shift->id }}" {{ old('shift_id') == $shift->id ? 'selected' : '' }}>{{ $shift->name }}: {{ $shift->timeRange() }}</option>
                        @endforeach
                    </select>
                </p>
            </div>
            <p id="assignment-message" class="assignment-message" hidden></p>

            <div id="single-date-fields" class="work-status-section">
                <div class="work-status-grid">
                    <p>Date<br><input type="date" name="work_date" value="{{ old('work_date', now()->toDateString()) }}"></p>
                </div>
            </div>

            <div id="date-range-fields" class="work-status-section" hidden>
                <p style="color:var(--muted);margin-top:0;">Use Date Range when adding multiple work status records at once.</p>
                <div class="work-status-grid">
                    <p>From Date<br><input type="date" name="from_date" value="{{ old('from_date', $prefill['from_date'] ?? '') }}"></p>
                    <p>To Date<br><input type="date" name="to_date" value="{{ old('to_date', $prefill['to_date'] ?? '') }}"></p>
                </div>
                <label style="display:flex;align-items:center;gap:8px;color:var(--muted);margin-top:14px;">
                    <input type="checkbox" name="confirm_after_last_working_date" value="1" {{ old('confirm_after_last_working_date') ? 'checked' : '' }}>
                    Confirm override if this range includes dates after employee last working date.
                </label>
            </div>

            <div id="monthly-cycle-fields" class="work-status-section" hidden>
                <div class="work-status-grid">
                    <p>Salary Month<br><input type="month" name="salary_month" id="salary-month" value="{{ old('salary_month', $prefill['salary_month'] ?? now()->format('Y-m')) }}"></p>
                    <p>Salary Cycle Date<br><input type="date" id="salary-cycle-date" readonly></p>
                    <p>From Date<br><input type="date" id="monthly-from-date" readonly></p>
                    <p>To Date<br><input type="date" id="monthly-to-date" readonly></p>
                    <p>Existing Dates<br>
                        <select name="duplicate_action" id="duplicate-action">
                            <option value="skip" {{ old('duplicate_action', 'skip') === 'skip' ? 'selected' : '' }}>Skip Existing</option>
                            <option value="update" {{ old('duplicate_action') === 'update' ? 'selected' : '' }}>Update Existing</option>
                        </select>
                    </p>
                </div>
                <p id="monthly-cycle-helper" style="color:var(--muted);margin:12px 0 0;">The first cycle starts from confirmation. Terminated employees stop at their last working date.</p>
            </div>

            <div class="work-status-section work-status-grid">
                <p id="default-day-type-field">Default Day Type<br>
                    <select id="default-day-type">
                        <option value="working">Working</option>
                        <option value="half_day">Half Day</option>
                        <option value="non_working">Non Working</option>
                    </select>
                </p>
                <p><span id="status-label">Status</span><br>
                    <select name="status" id="default-reason" required>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" {{ old('status', $prefill['status'] ?? 'working') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </p>
                <p>Note<br><textarea name="note" style="min-height:44px;">{{ old('note', $prefill['note'] ?? '') }}</textarea></p>
            </div>

            <div id="monthly-preview" class="work-status-section" hidden>
                <h2 style="margin-top:0;">Monthly Cycle Preview</h2>
                <p id="cycle-period-message" style="color:var(--muted);"></p>
                <div class="preview-wrap">
                    <table>
                            <thead><tr><th>Date</th><th>Day Type</th><th>Salary Count</th><th>Reason</th><th>Note</th></tr></thead>
                        <tbody id="monthly-preview-rows"></tbody>
                    </table>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;margin-top:18px;">
                <button class="btn" type="submit">Save Work Status</button>
            </div>
        </form>
    </div>

    <script>
        const assignmentDefaults = @json($assignmentDefaults);
        const statusLabels = @json($statuses);
        const salaryCountValues = @json($salaryCountValues);
        const statusTypes = {
            working: 'working', training: 'working', meeting: 'working', office_work: 'working', remote_work: 'working',
            half_day: 'half_day',
            absent: 'non_working', on_leave: 'non_working', sick_leave: 'non_working', client_issue: 'non_working', boosting_off: 'non_working', agency_closed: 'non_working'
        };
        const typeDefaultStatus = { working: 'working', half_day: 'half_day', non_working: 'absent' };
        const entryModeSelect = document.getElementById('work-status-entry-mode');
        const employeeSelect = document.getElementById('work-status-employee');
        const clientSelect = document.getElementById('work-status-client');
        const pageSelect = document.getElementById('work-status-page');
        const campaignSelect = document.getElementById('work-status-campaign');
        const shiftSelect = document.getElementById('work-status-shift');
        const assignmentMessage = document.getElementById('assignment-message');
        const salaryMonthInput = document.getElementById('salary-month');
        const cycleDateInput = document.getElementById('salary-cycle-date');
        const monthlyFromDateInput = document.getElementById('monthly-from-date');
        const monthlyToDateInput = document.getElementById('monthly-to-date');
        const defaultDayType = document.getElementById('default-day-type');
        const defaultReason = document.getElementById('default-reason');
        const previewRows = document.getElementById('monthly-preview-rows');

        const selectedText = (select, fallback) => select.value ? select.selectedOptions[0]?.textContent.trim() : fallback;
        const dateString = (date) => date.toISOString().slice(0, 10);
        const utcDate = (value) => new Date(`${value}T00:00:00Z`);
        const daysInMonth = (year, monthIndex) => new Date(Date.UTC(year, monthIndex + 1, 0)).getUTCDate();
        const cycleDate = (year, monthIndex, day) => new Date(Date.UTC(year, monthIndex, Math.min(day, daysInMonth(year, monthIndex))));

        const filterRelations = () => {
            const clientId = clientSelect.value;
            const pageId = pageSelect.value;
            pageSelect.querySelectorAll('option[data-client-id]').forEach((option) => {
                option.hidden = !!clientId && option.dataset.clientId !== clientId;
            });
            if (pageSelect.selectedOptions[0]?.hidden) pageSelect.value = '';

            campaignSelect.querySelectorAll('option[data-client-id]').forEach((option) => {
                const clientMatches = !clientId || option.dataset.clientId === clientId;
                const pageMatches = !pageId || option.dataset.pageId === pageId;
                option.hidden = !(clientMatches && pageMatches);
            });
            if (campaignSelect.selectedOptions[0]?.hidden) campaignSelect.value = '';
        };

        const applyEmployeeDefaults = () => {
            const defaults = assignmentDefaults[employeeSelect.value];
            assignmentMessage.hidden = true;
            assignmentMessage.classList.remove('warning');
            clientSelect.value = '';
            pageSelect.value = '';
            campaignSelect.value = '';
            shiftSelect.value = '';

            if (!defaults) {
                renderMonthlyPreview();
                return;
            }

            if (defaults.is_agency_internal) {
                shiftSelect.value = defaults.shift_id || '';
                assignmentMessage.textContent = defaults.has_assignment
                    ? 'Agency Internal employee: default shift loaded; client and page remain empty.'
                    : 'No active assignment found. Select manually.';
                assignmentMessage.hidden = false;
                assignmentMessage.classList.toggle('warning', !defaults.has_assignment);
                filterRelations();
                renderMonthlyPreview();
                return;
            }

            if (!defaults.has_assignment) {
                shiftSelect.value = defaults.shift_id || '';
                assignmentMessage.textContent = 'No active assignment found. Select manually.';
                assignmentMessage.hidden = false;
                assignmentMessage.classList.add('warning');
                filterRelations();
                renderMonthlyPreview();
                return;
            }

            clientSelect.value = defaults.client_id || '';
            filterRelations();
            pageSelect.value = defaults.client_page_id || '';
            filterRelations();
            campaignSelect.value = defaults.campaign_id || '';
            shiftSelect.value = defaults.shift_id || '';
            assignmentMessage.textContent = 'Latest active assignment loaded.';
            assignmentMessage.hidden = false;
            renderMonthlyPreview();
        };

        const cyclePeriod = () => {
            const defaults = assignmentDefaults[employeeSelect.value];
            if (!defaults?.confirmation_date || !defaults?.salary_day || !salaryMonthInput.value) return null;

            if (defaults.status === 'terminated' && defaults.final_settlement_period) {
                return {
                    start: utcDate(defaults.final_settlement_period.period_start),
                    end: utcDate(defaults.final_settlement_period.period_end),
                    salaryDate: utcDate(defaults.final_settlement_period.salary_cycle_date),
                    isFinalSettlement: true
                };
            }

            const [year, month] = salaryMonthInput.value.split('-').map(Number);
            const monthIndex = month - 1;
            const confirmation = utcDate(defaults.confirmation_date);
            let end = cycleDate(year, monthIndex, Number(defaults.salary_day));
            if (end <= confirmation) {
                end = cycleDate(year, monthIndex + 1, Number(defaults.salary_day));
            }
            const previous = cycleDate(end.getUTCFullYear(), end.getUTCMonth() - 1, Number(defaults.salary_day));
            const start = confirmation >= previous
                ? confirmation
                : new Date(Date.UTC(previous.getUTCFullYear(), previous.getUTCMonth(), previous.getUTCDate() + 1));

            let eligibleEnd = end;
            if (defaults.last_working_date) {
                const lastWorking = utcDate(defaults.last_working_date);
                if (lastWorking < eligibleEnd) eligibleEnd = lastWorking;
            }

            if (confirmation > eligibleEnd || start > eligibleEnd) return null;
            return { start, end: eligibleEnd, salaryDate: end, isFinalSettlement: false };
        };

        const statusOptions = (selected) => Object.entries(statusLabels).map(([value, label]) =>
            `<option value="${value}" ${value === selected ? 'selected' : ''}>${label}</option>`
        ).join('');

        const renderMonthlyPreview = () => {
            if (entryModeSelect.value !== 'monthly') return;
            const period = cyclePeriod();
            const priorRows = {};
            previewRows.querySelectorAll('tr[data-date]').forEach((row) => {
                priorRows[row.dataset.date] = {
                    status: row.querySelector('.row-reason')?.value,
                    note: row.querySelector('.row-note')?.value,
                };
            });
            previewRows.innerHTML = '';

            if (!period) {
                cycleDateInput.value = '';
                monthlyFromDateInput.value = '';
                monthlyToDateInput.value = '';
                document.getElementById('monthly-cycle-helper').textContent = 'The first cycle starts from confirmation. Terminated employees stop at their last working date.';
                document.getElementById('cycle-period-message').textContent = 'Select a salary-eligible employee and salary month to preview the cycle.';
                return;
            }

            cycleDateInput.value = dateString(period.salaryDate);
            monthlyFromDateInput.value = dateString(period.start);
            monthlyToDateInput.value = dateString(period.end);
            document.getElementById('monthly-cycle-helper').textContent = period.isFinalSettlement
                ? 'Final settlement cycle detected. Work status will be generated up to the last working date.'
                : 'The first cycle starts from confirmation. Terminated employees stop at their last working date.';
            document.getElementById('cycle-period-message').textContent = period.isFinalSettlement
                ? `Final settlement period: ${dateString(period.start)} to ${dateString(period.end)}`
                : `Cycle period: ${dateString(period.start)} to ${dateString(period.end)}`;
            let index = 0;

            for (let date = new Date(period.start.getTime()); date <= period.end; date.setUTCDate(date.getUTCDate() + 1)) {
                const value = dateString(date);
                const status = priorRows[value]?.status || defaultReason.value;
                const note = priorRows[value]?.note ?? document.querySelector('textarea[name="note"]').value;
                const type = statusTypes[status] || defaultDayType.value;
                const row = document.createElement('tr');
                row.dataset.date = value;
                row.innerHTML = `
                    <td>${value}<input type="hidden" name="monthly_rows[${index}][date]" value="${value}"></td>
                    <td><select name="monthly_rows[${index}][day_type]" class="row-day-type">
                        <option value="working" ${type === 'working' ? 'selected' : ''}>Working</option>
                        <option value="half_day" ${type === 'half_day' ? 'selected' : ''}>Half Day</option>
                        <option value="non_working" ${type === 'non_working' ? 'selected' : ''}>Non Working</option>
                    </select></td>
                    <td class="row-count">${Number(salaryCountValues[status] || 0).toFixed(2)}</td>
                    <td><select name="monthly_rows[${index}][status]" class="row-reason">${statusOptions(status)}</select></td>
                    <td><input type="text" name="monthly_rows[${index}][note]" class="row-note" value="${note.replaceAll('&', '&amp;').replaceAll('"', '&quot;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')}"></td>`;
                previewRows.appendChild(row);
                index++;
            }
        };

        const syncEntryMode = () => {
            const mode = entryModeSelect.value;
            document.getElementById('single-date-fields').hidden = mode !== 'single';
            document.getElementById('date-range-fields').hidden = mode !== 'range';
            document.getElementById('monthly-cycle-fields').hidden = mode !== 'monthly';
            document.getElementById('monthly-preview').hidden = mode !== 'monthly';
            document.getElementById('default-day-type-field').hidden = mode !== 'monthly';
            document.getElementById('status-label').textContent = mode === 'monthly' ? 'Default Reason' : 'Status';
            document.querySelector('input[name="work_date"]').required = mode === 'single';
            document.querySelector('input[name="from_date"]').required = mode === 'range';
            document.querySelector('input[name="to_date"]').required = mode === 'range';
            salaryMonthInput.required = mode === 'monthly';
            renderMonthlyPreview();
        };

        employeeSelect.addEventListener('change', applyEmployeeDefaults);
        entryModeSelect.addEventListener('change', syncEntryMode);
        salaryMonthInput.addEventListener('change', renderMonthlyPreview);
        clientSelect.addEventListener('change', () => { filterRelations(); renderMonthlyPreview(); });
        pageSelect.addEventListener('change', () => { filterRelations(); renderMonthlyPreview(); });
        shiftSelect.addEventListener('change', renderMonthlyPreview);
        defaultDayType.addEventListener('change', () => {
            defaultReason.value = typeDefaultStatus[defaultDayType.value];
            renderMonthlyPreview();
        });
        defaultReason.addEventListener('change', () => {
            defaultDayType.value = statusTypes[defaultReason.value] || 'non_working';
            renderMonthlyPreview();
        });
        previewRows.addEventListener('change', (event) => {
            const row = event.target.closest('tr');
            if (!row) return;
            const reason = row.querySelector('.row-reason');
            const dayType = row.querySelector('.row-day-type');
            if (event.target.classList.contains('row-day-type')) reason.value = typeDefaultStatus[dayType.value];
            if (event.target.classList.contains('row-reason')) dayType.value = statusTypes[reason.value] || 'non_working';
            row.querySelector('.row-count').textContent = Number(salaryCountValues[reason.value] || 0).toFixed(2);
        });

        filterRelations();
        syncEntryMode();
        if (employeeSelect.value) applyEmployeeDefaults();
    </script>
@endsection
