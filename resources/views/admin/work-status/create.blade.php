@extends('layouts.admin')

@section('content')
    <h1>Add Work Status</h1>
    <a class="btn" href="/admin/work-status">Back to Work Status</a>

    <div class="card" style="margin-top:20px;">
        <form method="POST" action="/admin/work-status">
            @csrf
            <p>Employee<br>
                <select name="employee_id" id="work-status-employee" required>
                    <option value="">Select Employee</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }} ({{ $employee->employee_id }})
                        </option>
                    @endforeach
                </select>
            </p>
            <p>Client<br>
                <select name="client_id" class="js-client-select" data-page-target="work-status-page-create">
                    <option value="">No Client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>{{ $client->company_name }}</option>
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
            <p>Shift<br>
                <select name="shift_id">
                    <option value="">No Shift</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}" {{ old('shift_id') == $shift->id ? 'selected' : '' }}>{{ $shift->name }}: {{ $shift->timeRange() }}</option>
                    @endforeach
                </select>
            </p>
            <p>Date<br><input type="date" name="work_date" value="{{ old('work_date', now()->toDateString()) }}" required></p>
            <p>Status<br>
                <select name="status" required>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" {{ old('status', 'working') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </p>
            <p>Note<br><textarea name="note">{{ old('note') }}</textarea></p>
            <button class="btn" type="submit">Save Work Status</button>
        </form>
    </div>
    <script>
        const assignmentDefaults = @json($employees->mapWithKeys(function ($employee) {
            $assignment = $employee->activeAssignments->sortByDesc('assigned_from')->first();

            return [$employee->id => [
                'client_id' => $assignment?->client_id,
                'client_page_id' => $assignment?->client_page_id,
                'shift_id' => $assignment?->shift_id ?: $employee->shift_id,
            ]];
        }));

        document.querySelectorAll('.js-client-select').forEach((clientSelect) => {
            const pageSelect = document.getElementById(clientSelect.dataset.pageTarget);
            const filterPages = () => {
                const clientId = clientSelect.value;
                pageSelect.querySelectorAll('option[data-client-id]').forEach((option) => {
                    option.hidden = clientId && option.dataset.clientId !== clientId;
                });
                if (pageSelect.selectedOptions[0]?.hidden) {
                    pageSelect.value = '';
                }
            };
            clientSelect.addEventListener('change', filterPages);
            filterPages();
        });

        document.getElementById('work-status-employee')?.addEventListener('change', (event) => {
            const defaults = assignmentDefaults[event.target.value] || {};
            const clientSelect = document.querySelector('select[name="client_id"]');
            const pageSelect = document.querySelector('select[name="client_page_id"]');
            const shiftSelect = document.querySelector('select[name="shift_id"]');

            if (defaults.client_id) {
                clientSelect.value = defaults.client_id;
                clientSelect.dispatchEvent(new Event('change'));
            }

            if (defaults.client_page_id) {
                pageSelect.value = defaults.client_page_id;
            }

            if (defaults.shift_id) {
                shiftSelect.value = defaults.shift_id;
            }
        });
    </script>
@endsection
