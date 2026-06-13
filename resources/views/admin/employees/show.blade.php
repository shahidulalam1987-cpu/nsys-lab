@extends('layouts.admin')

@section('content')
    <h1>Employee Profile</h1>

    <style>
        .employee-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
        }

        .employee-actions form {
            display: inline;
            margin: 0;
        }

        .employee-tabs {
            display: flex;
            gap: 8px;
            margin: 20px 0 16px;
            overflow-x: auto;
            padding-bottom: 4px;
        }

        .employee-tab-button {
            background: #111827;
            border: 1px solid #243044;
            border-radius: 8px;
            color: #cbd5e1;
            cursor: pointer;
            flex: 0 0 auto;
            padding: 10px 14px;
        }

        .employee-tab-button.active {
            background: #2563eb;
            border-color: #3b82f6;
            color: #fff;
        }

        .employee-tab-panel {
            display: none;
        }

        .employee-tab-panel.active {
            display: block;
        }

        .employee-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .employee-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(180px, 1fr));
            gap: 8px 18px;
        }

        .employee-info-grid p {
            margin: 0;
        }

        .assignment-form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 10px;
            align-items: end;
        }

        .assignment-form-grid input,
        .assignment-form-grid select {
            width: 100%;
            box-sizing: border-box;
        }

        .employee-profile-photo {
            align-items: center;
            background: #111827;
            border: 1px solid #243044;
            border-radius: 10px;
            display: flex;
            height: 112px;
            justify-content: center;
            overflow: hidden;
            width: 112px;
        }

        .employee-profile-photo img {
            height: 100%;
            object-fit: cover;
            width: 100%;
        }

        .employee-avatar-placeholder {
            color: #dbeafe;
            font-size: 32px;
            font-weight: 700;
        }

        @media (max-width: 900px) {
            .employee-grid,
            .employee-info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @php
        $initials = collect(explode(' ', $employee->name))
            ->filter()
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->implode('');
        $documentFields = [
            'nid_front_file' => 'NID Front',
            'nid_back_file' => 'NID Back',
            'cv_file' => 'CV',
            'appointment_letter_file' => 'Appointment Letter',
            'agreement_file' => 'Agreement',
        ];
    @endphp

    <div class="employee-actions">
        <a class="btn" href="/admin/employees">Back to Employees</a>
        <a class="btn" href="/admin/employees/{{ $employee->id }}/edit">Edit Employee</a>

        @if($employee->isEligibleForConfirmation())
            <form method="POST" action="/admin/employees/{{ $employee->id }}/confirm">
                @csrf
                <button class="btn btn-success" type="submit">Confirm Employee</button>
            </form>
        @endif

        <form method="POST" action="/admin/employees/{{ $employee->id }}/terminate">
            @csrf
            <button class="btn btn-danger" type="submit" onclick="return confirm('Terminate this employee? History and login will be preserved.');">Deactivate / Terminate</button>
        </form>

        <form method="POST" action="/admin/employees/{{ $employee->id }}/delete">
            @csrf
            <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this employee? This is allowed only when no history exists.');">Delete</button>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><p>Monthly Salary</p><h2>BDT {{ number_format($employee->monthly_salary, 2) }}</h2></div>
        <div class="stat-card"><p>Total Paid Salary</p><h2>BDT {{ number_format($salarySummary['total_paid_salary'], 2) }}</h2></div>
        <div class="stat-card"><p>Current Salary Due</p><h2>BDT {{ number_format($salarySummary['current_salary_due'], 2) }}</h2></div>
        <div class="stat-card"><p>Current Status</p><h2>{{ $employee->statusLabel() }}</h2></div>
    </div>

    @if($employee->status === 'terminated')
        <div class="card" style="border-color:#f59e0b;">
            <h2>Final Settlement</h2>
            <div class="stats-grid">
                <div class="stat-card"><p>Payable Salary</p><h2>BDT {{ number_format($salarySummary['final_settlement_payable'], 2) }}</h2></div>
                <div class="stat-card"><p>Paid Salary</p><h2>BDT {{ number_format($salarySummary['final_settlement_paid'], 2) }}</h2></div>
                <div class="stat-card"><p>Remaining Due</p><h2>BDT {{ number_format($salarySummary['final_settlement_due'], 2) }}</h2></div>
                <div class="stat-card"><p>Last Working Date</p><h2>{{ $employee->last_working_date?->toDateString() ?: '-' }}</h2></div>
                <div class="stat-card"><p>Final Settlement Status</p><h2>{{ $salarySummary['final_settlement_status'] }}</h2></div>
            </div>
        </div>
    @endif

    <div class="employee-tabs" role="tablist">
        <button class="employee-tab-button active" type="button" data-tab="overview">Overview</button>
        <button class="employee-tab-button" type="button" data-tab="salary">Salary</button>
        <button class="employee-tab-button" type="button" data-tab="salary-ledger">Salary Ledger</button>
        <button class="employee-tab-button" type="button" data-tab="assignment">Assignments</button>
        <button class="employee-tab-button" type="button" data-tab="banking">Banking</button>
        <button class="employee-tab-button" type="button" data-tab="login">Login</button>
        <button class="employee-tab-button" type="button" data-tab="documents">Documents</button>
        <button class="employee-tab-button" type="button" data-tab="notes">Notes</button>
    </div>

    <div class="employee-tab-panel active" data-tab-panel="overview">
        <div class="employee-grid">
            <div class="card" style="margin-top:0;">
                <h2>Basic Information</h2>
                <div class="employee-profile-photo" style="margin-bottom:16px;">
                    @if($employee->profile_photo)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($employee->profile_photo) }}" alt="{{ $employee->name }}">
                    @else
                        <div class="employee-avatar-placeholder">{{ $initials ?: 'EM' }}</div>
                    @endif
                </div>
                <div class="employee-info-grid">
                    <p><strong>Employee ID:</strong> {{ $employee->employee_id }}</p>
                    <p><strong>Employment Type:</strong> <span class="badge badge-info">{{ $employee->employeeTypeLabel() }}</span></p>
                    <p><strong>Full Name:</strong> {{ $employee->name }}</p>
                    <p><strong>Mobile:</strong> {{ $employee->mobile ?: '-' }}</p>
                    <p><strong>Email:</strong> {{ $employee->email ?: '-' }}</p>
                    <p><strong>Address:</strong> {{ $employee->address ?: '-' }}</p>
                    <p><strong>NID Number:</strong> {{ $employee->nid_number ?: '-' }}</p>
                    <p><strong>Date of Birth:</strong> {{ $employee->date_of_birth?->toDateString() ?: '-' }}</p>
                    <p><strong>Gender:</strong> {{ $employee->gender ? ucfirst($employee->gender) : '-' }}</p>
                </div>
            </div>

            <div class="card" style="margin-top:0;">
                <h2>Employment Information</h2>
                <div class="employee-info-grid">
                    <p><strong>Department:</strong> {{ $employee->department }}</p>
                    <p><strong>Role:</strong> {{ $employee->role }}</p>
                    <p><strong>Salary Source:</strong> {{ $employee->salarySourceLabel() }}</p>
                    <p><strong>Permission Group:</strong> {{ $employee->permissionGroupLabel() }}</p>
                    <p><strong>Shift Name:</strong> {{ $employee->shift?->name ?: '-' }}</p>
                    <p><strong>Shift Time:</strong> {{ $employee->shift?->timeRange() ?: '-' }}</p>
                    <p><strong>Joining Date:</strong> {{ $employee->joining_date?->toDateString() }}</p>
                    <p><strong>Confirmation Date:</strong> {{ $employee->confirmation_date?->toDateString() ?: '-' }}</p>
                    <p><strong>Salary Day:</strong> {{ $employee->salaryCycleDay() ?: '-' }}</p>
                    <p><strong>Next Salary Date:</strong> {{ $employee->nextSalaryDate()?->toDateString() ?: '-' }}</p>
                    <p><strong>Monthly Salary:</strong> BDT {{ number_format($employee->monthly_salary, 2) }}</p>
                    <p><strong>Current Status:</strong> {{ $employee->statusLabel() }}</p>
                    <p><strong>Current Salary Status:</strong> {{ $employee->salaryStatusLabel() }}</p>
                    <p><strong>Last Working Date:</strong> {{ $employee->last_working_date?->toDateString() ?: '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="employee-tab-panel" data-tab-panel="salary">
        <div class="card" style="margin-top:0;">
            <h2>Salary Overview</h2>
            <div class="employee-info-grid">
                <p><strong>Monthly Salary:</strong> BDT {{ number_format($employee->monthly_salary, 2) }}</p>
                <p><strong>Salary Source:</strong> {{ $employee->salarySourceLabel() }}</p>
                <p><strong>Assigned Client:</strong> {{ $salarySummary['assigned_client']?->company_name ?: '-' }}</p>
                <p>
                    <strong>Client Fund Balance:</strong>
                    {{ $salarySummary['client_fund_balance'] !== null ? 'BDT ' . number_format($salarySummary['client_fund_balance'], 2) : '-' }}
                </p>
                <p><strong>Upcoming Salary Date:</strong> {{ $salarySummary['upcoming_salary_date']?->toDateString() ?: '-' }}</p>
                <p><strong>Salary Status:</strong> {{ $salarySummary['salary_status'] }}</p>
                <p><strong>Working Days:</strong> {{ number_format($salarySummary['working_days']) }}</p>
                <p><strong>Non Working Days:</strong> {{ number_format($salarySummary['non_working_days']) }}</p>
                <p><strong>Total Payable Salary:</strong> BDT {{ number_format($salarySummary['total_payable_salary'], 2) }}</p>
                <p><strong>Total Paid Salary:</strong> BDT {{ number_format($salarySummary['total_paid_salary'], 2) }}</p>
                <p><strong>Current Salary Due:</strong> BDT {{ number_format($salarySummary['current_salary_due'], 2) }}</p>
                <p><strong>Current Salary Status:</strong> {{ $employee->salaryStatusLabel() }}</p>
                <p>
                    <strong>Last Salary Payment:</strong>
                    @if($salarySummary['last_salary_payment'])
                        BDT {{ number_format($salarySummary['last_salary_payment']->paid_amount, 2) }}
                        on {{ $salarySummary['last_salary_payment']->payment_date?->toDateString() ?: $salarySummary['last_salary_payment']->created_at?->toDateString() }}
                    @else
                        -
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="employee-tab-panel" data-tab-panel="salary-ledger">
        <div class="stats-grid" style="margin-top:0;">
            <div class="stat-card"><p>Total Generated Salary</p><h2>BDT {{ number_format($salaryLedgerSummary['total_generated'], 2) }}</h2></div>
            <div class="stat-card"><p>Total Paid Salary</p><h2>BDT {{ number_format($salaryLedgerSummary['total_paid'], 2) }}</h2></div>
            <div class="stat-card"><p>Current Due</p><h2>BDT {{ number_format($salaryLedgerSummary['current_due'], 2) }}</h2></div>
            <div class="stat-card"><p>Last Payment Date</p><h2>{{ $salaryLedgerSummary['last_payment_date']?->toDateString() ?: '-' }}</h2></div>
        </div>

        <div class="card" style="margin-top:0;">
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px;">
                <h2 style="margin:0;">Salary Ledger</h2>
                <p style="margin:0;">
                    <a class="btn" href="/admin/employees/{{ $employee->id }}/salary-ledger/export/csv">Export CSV</a>
                    <a class="btn" href="/admin/employees/{{ $employee->id }}/salary-ledger/export/excel">Export Excel</a>
                </p>
            </div>

            <div class="table-wrap" style="margin-top:16px;">
                <table>
                    <tr>
                        <th>Month</th>
                        <th>Client</th>
                        <th>Working Days</th>
                        <th>Non Working Days</th>
                        <th>Generated Salary</th>
                        <th>Paid Amount</th>
                        <th>Due Amount</th>
                        <th>Status</th>
                        <th>Generated Date</th>
                        <th>Paid Date</th>
                    </tr>
                    @forelse($salaryLedgerRows as $row)
                        <tr>
                            <td>
                                <a href="/admin/payroll/{{ $row['payroll']->id }}">{{ $row['month'] }}</a>
                            </td>
                            <td>{{ $row['client'] }}</td>
                            <td>{{ $row['working_days'] }}</td>
                            <td>{{ $row['non_working_days'] }}</td>
                            <td>BDT {{ number_format($row['generated_salary'], 2) }}</td>
                            <td>BDT {{ number_format($row['paid_amount'], 2) }}</td>
                            <td>BDT {{ number_format($row['due_amount'], 2) }}</td>
                            <td>{{ $row['status'] }}</td>
                            <td>{{ $row['generated_date'] }}</td>
                            <td>{{ $row['paid_date'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10">No salary ledger records found.</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
    </div>

    <div class="employee-tab-panel" data-tab-panel="assignment">
        <div class="card" style="margin-top:0;">
            <h2>Assign to Client/Page/Shift</h2>
            @if($employee->isAgencyInternal())
                <p>Agency Internal employees do not require client/page/campaign assignment. Use Assignment Management only if this employee needs a special client context.</p>
            @else
            <form method="POST" action="/admin/employees/{{ $employee->id }}/assignments">
                @csrf
                <div class="assignment-form-grid">
                    <select name="client_id" class="js-client-select" data-page-target="assignment-page-create" required>
                        <option value="">Select Client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                        @endforeach
                    </select>
                    <select id="assignment-page-create" name="client_page_id">
                        <option value="">Client Only / No Page</option>
                        @foreach($clientPages as $page)
                            <option value="{{ $page->id }}" data-client-id="{{ $page->client_id }}">
                                {{ $page->page_name }} ({{ $page->platform }})
                            </option>
                        @endforeach
                    </select>
                    <select id="assignment-campaign-create" name="campaign_id">
                        <option value="">No Campaign</option>
                        @foreach($campaigns as $campaign)
                            <option value="{{ $campaign->id }}" data-client-id="{{ $campaign->client_id }}" data-page-id="{{ $campaign->client_page_id }}">
                                {{ $campaign->campaign_name }} - {{ $campaign->campaign_id }}
                            </option>
                        @endforeach
                    </select>
                    <select name="shift_id">
                        <option value="">Default / No Shift</option>
                        @foreach($shifts as $shift)
                            <option value="{{ $shift->id }}">{{ $shift->name }}: {{ $shift->timeRange() }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="assigned_from" required>
                    <input type="date" name="assigned_to">
                    <select name="status" required>
                        <option value="active">Active</option>
                        <option value="ended">Ended</option>
                    </select>
                    <input type="text" name="note" placeholder="Note">
                    <button class="btn" type="submit">Save Assignment</button>
                </div>
            </form>
            @endif
        </div>

        <div class="card">
            <h2>Assignments</h2>
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Client</th>
                        <th>Page</th>
                        <th>Campaign</th>
                        <th>Shift</th>
                        <th>Assigned Date</th>
                        <th>To</th>
                        <th>Status</th>
                        <th>Note</th>
                        <th>Actions</th>
                    </tr>
                    @forelse($employee->assignments->sortByDesc('assigned_from') as $assignment)
                        <tr>
                            <td>{{ $assignment->client?->company_name }}</td>
                            <td>{{ $assignment->page?->page_name ?: '-' }}</td>
                            <td>{{ $assignment->campaignRecord?->campaign_name ?: ($assignment->campaign ?: '-') }}</td>
                            <td>{{ $assignment->shift?->name ?: '-' }}</td>
                            <td>{{ $assignment->assigned_from?->toDateString() }}</td>
                            <td>{{ $assignment->assigned_to?->toDateString() ?: '-' }}</td>
                            <td>{{ $assignment->statusLabel() }}</td>
                            <td>{{ $assignment->note ?: '-' }}</td>
                            <td>
                                <form method="POST" action="/admin/employee-assignments/{{ $assignment->id }}/update" style="display:inline;">
                                    @csrf
                                    <select name="client_page_id">
                                        <option value="">No Page</option>
                                        @foreach($clientPages->where('client_id', $assignment->client_id) as $page)
                                            <option value="{{ $page->id }}" {{ $assignment->client_page_id == $page->id ? 'selected' : '' }}>
                                                {{ $page->page_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <select name="shift_id">
                                        <option value="">No Shift</option>
                                        @foreach($shifts as $shift)
                                            <option value="{{ $shift->id }}" {{ $assignment->shift_id == $shift->id ? 'selected' : '' }}>
                                                {{ $shift->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <select name="campaign_id">
                                        <option value="">No Campaign</option>
                                        @foreach($campaigns->where('client_id', $assignment->client_id) as $campaign)
                                            <option value="{{ $campaign->id }}" {{ $assignment->campaign_id == $campaign->id ? 'selected' : '' }}>
                                                {{ $campaign->campaign_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($assignment->campaign)
                                        <input type="hidden" name="campaign" value="{{ $assignment->campaign }}">
                                    @endif
                                    <input type="date" name="assigned_to" value="{{ $assignment->assigned_to?->toDateString() }}">
                                    <select name="status">
                                        <option value="active" {{ $assignment->status == 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="ended" {{ $assignment->status == 'ended' ? 'selected' : '' }}>Ended</option>
                                    </select>
                                    <input type="text" name="note" value="{{ $assignment->note }}">
                                    <button type="submit">Update</button>
                                </form>

                                <form method="POST" action="/admin/employee-assignments/{{ $assignment->id }}/delete" style="display:inline;">
                                    @csrf
                                    <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this assignment record?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9">No assignment history found.</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
    </div>

    <div class="employee-tab-panel" data-tab-panel="banking">
        <div class="card" style="margin-top:0;">
            <h2>Banking Information</h2>
            <div class="employee-info-grid">
                <p><strong>Bank Name:</strong> {{ $employee->bank_name ?: '-' }}</p>
                <p><strong>Account Name:</strong> {{ $employee->account_name ?: '-' }}</p>
                <p><strong>Account Number:</strong> {{ $employee->account_number ?: '-' }}</p>
                <p><strong>Branch Name:</strong> {{ $employee->branch_name ?: '-' }}</p>
            </div>
        </div>
    </div>

    <div class="employee-tab-panel" data-tab-panel="login">
        <div class="card" style="margin-top:0;">
            <h2>Login Information</h2>
            <div class="employee-info-grid">
                <p><strong>Login Status:</strong> {{ $employee->user_id ? 'Login Linked' : 'No Login Linked' }}</p>
                <p><strong>Login Email:</strong> {{ $employee->user?->email ?: '-' }}</p>
            </div>

            @if(! $employee->user_id)
                <p style="margin-top:16px;"><a class="btn" href="/admin/employees/{{ $employee->id }}/create-login">Create Employee Login</a></p>
            @else
                <p style="margin-top:16px;"><a class="btn" href="/admin/employees/{{ $employee->id }}/reset-login-password">Reset Password</a></p>
            @endif
        </div>
    </div>

    <div class="employee-tab-panel" data-tab-panel="documents">
        <div class="card" style="margin-top:0;">
            <h2>Documents</h2>
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Document</th>
                        <th>Status</th>
                        <th>File</th>
                    </tr>
                    @foreach($documentFields as $field => $label)
                        <tr>
                            <td>{{ $label }}</td>
                            <td>{{ $employee->{$field} ? 'Uploaded' : 'Not Uploaded' }}</td>
                            <td>
                                @if($employee->{$field})
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($employee->{$field}) }}" target="_blank">View / Download</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>

    <div class="employee-tab-panel" data-tab-panel="notes">
        <div class="card" style="margin-top:0;">
            <h2>Admin Notes</h2>
            <p>{{ $employee->admin_note ?: 'No admin note added yet.' }}</p>
        </div>
    </div>

    <script>
        const tabButtons = document.querySelectorAll('.employee-tab-button');
        const tabPanels = document.querySelectorAll('.employee-tab-panel');

        tabButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const tab = button.dataset.tab;

                tabButtons.forEach((item) => item.classList.toggle('active', item === button));
                tabPanels.forEach((panel) => {
                    panel.classList.toggle('active', panel.dataset.tabPanel === tab);
                });
            });
        });

        document.querySelectorAll('.js-client-select').forEach((clientSelect) => {
            const pageSelect = document.getElementById(clientSelect.dataset.pageTarget);
            const campaignSelect = document.getElementById('assignment-campaign-create');
            const filterRelations = () => {
                const clientId = clientSelect.value;
                const pageId = pageSelect.value;
                pageSelect.querySelectorAll('option[data-client-id]').forEach((option) => {
                    option.hidden = clientId && option.dataset.clientId !== clientId;
                });
                if (pageSelect.selectedOptions[0]?.hidden) {
                    pageSelect.value = '';
                }

                if (campaignSelect) {
                    campaignSelect.querySelectorAll('option[data-client-id]').forEach((option) => {
                        const clientMatches = !clientId || option.dataset.clientId === clientId;
                        const pageMatches = !pageId || option.dataset.pageId === pageId;
                        option.hidden = !(clientMatches && pageMatches);
                    });
                    if (campaignSelect.selectedOptions[0]?.hidden) {
                        campaignSelect.value = '';
                    }
                }
            };

            clientSelect.addEventListener('change', filterRelations);
            pageSelect.addEventListener('change', filterRelations);
            filterRelations();
        });
    </script>
@endsection
