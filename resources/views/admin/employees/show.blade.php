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

        @media (max-width: 900px) {
            .employee-grid,
            .employee-info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

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

    <div class="employee-tabs" role="tablist">
        <button class="employee-tab-button active" type="button" data-tab="overview">Overview</button>
        <button class="employee-tab-button" type="button" data-tab="salary">Salary</button>
        <button class="employee-tab-button" type="button" data-tab="assignment">Assignment</button>
        <button class="employee-tab-button" type="button" data-tab="banking">Banking</button>
        <button class="employee-tab-button" type="button" data-tab="login">Login</button>
        <button class="employee-tab-button" type="button" data-tab="documents">Documents</button>
        <button class="employee-tab-button" type="button" data-tab="notes">Notes</button>
    </div>

    <div class="employee-tab-panel active" data-tab-panel="overview">
        <div class="employee-grid">
            <div class="card" style="margin-top:0;">
                <h2>Basic Information</h2>
                <div class="employee-info-grid">
                    <p><strong>Profile Photo:</strong> Photo upload not added yet</p>
                    <p><strong>Employee ID:</strong> {{ $employee->employee_id }}</p>
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
                    <p><strong>Joining Date:</strong> {{ $employee->joining_date?->toDateString() }}</p>
                    <p><strong>Confirmation Date:</strong> {{ $employee->confirmation_date?->toDateString() ?: '-' }}</p>
                    <p><strong>Salary Cycle Day:</strong> {{ $employee->salary_day ?: '-' }}</p>
                    <p><strong>Next Salary Date:</strong> {{ $employee->nextSalaryDate()?->toDateString() ?: '-' }}</p>
                    <p><strong>Monthly Salary:</strong> BDT {{ number_format($employee->monthly_salary, 2) }}</p>
                    <p><strong>Current Status:</strong> {{ $employee->statusLabel() }}</p>
                    <p><strong>Last Working Date:</strong> {{ $employee->last_working_date?->toDateString() ?: '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="employee-tab-panel" data-tab-panel="salary">
        <div class="card" style="margin-top:0;">
            <h2>Salary Information Summary</h2>
            <div class="employee-info-grid">
                <p><strong>Monthly Salary:</strong> BDT {{ number_format($employee->monthly_salary, 2) }}</p>
                <p><strong>Working Days:</strong> {{ number_format($salarySummary['working_days']) }}</p>
                <p><strong>Non Working Days:</strong> {{ number_format($salarySummary['non_working_days']) }}</p>
                <p><strong>Total Payable Salary:</strong> BDT {{ number_format($salarySummary['total_payable_salary'], 2) }}</p>
                <p><strong>Total Paid Salary:</strong> BDT {{ number_format($salarySummary['total_paid_salary'], 2) }}</p>
                <p><strong>Current Salary Due:</strong> BDT {{ number_format($salarySummary['current_salary_due'], 2) }}</p>
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

    <div class="employee-tab-panel" data-tab-panel="assignment">
        <div class="card" style="margin-top:0;">
            <h2>Assign to Client</h2>
            <form method="POST" action="/admin/employees/{{ $employee->id }}/assignments">
                @csrf
                <div class="assignment-form-grid">
                    <select name="client_id" required>
                        <option value="">Select Client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->company_name }}</option>
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
        </div>

        <div class="card">
            <h2>Assignment History</h2>
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Client</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Status</th>
                        <th>Note</th>
                        <th>Actions</th>
                    </tr>
                    @forelse($employee->assignments->sortByDesc('assigned_from') as $assignment)
                        <tr>
                            <td>{{ $assignment->client?->company_name }}</td>
                            <td>{{ $assignment->assigned_from?->toDateString() }}</td>
                            <td>{{ $assignment->assigned_to?->toDateString() ?: '-' }}</td>
                            <td>{{ ucfirst($assignment->status) }}</td>
                            <td>{{ $assignment->note ?: '-' }}</td>
                            <td>
                                <form method="POST" action="/admin/employee-assignments/{{ $assignment->id }}/update" style="display:inline;">
                                    @csrf
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
                        <tr><td colspan="6">No assignment history found.</td></tr>
                    @endforelse
                </table>
            </div>
        </div>
    </div>

    <div class="employee-tab-panel" data-tab-panel="banking">
        <div class="card" style="margin-top:0;">
            <h2>Banking / Mobile Banking Information</h2>
            <div class="employee-info-grid">
                <p><strong>Bank Name:</strong> {{ $employee->bank_name ?: '-' }}</p>
                <p><strong>Account Name:</strong> {{ $employee->account_name ?: '-' }}</p>
                <p><strong>Account Number:</strong> {{ $employee->account_number ?: '-' }}</p>
                <p><strong>Branch Name:</strong> {{ $employee->branch_name ?: '-' }}</p>
                <p><strong>bKash Number:</strong> {{ $employee->bkash_number ?: '-' }}</p>
                <p><strong>Nagad Number:</strong> {{ $employee->nagad_number ?: '-' }}</p>
                <p><strong>Rocket Number:</strong> {{ $employee->rocket_number ?: '-' }}</p>
                <p><strong>Preferred Payment Method:</strong> {{ $employee->preferred_payment_method ? ucfirst($employee->preferred_payment_method) : '-' }}</p>
                <p><strong>Mobile Banking Info:</strong> {{ $employee->mobile_banking_info ?: '-' }}</p>
            </div>
        </div>
    </div>

    <div class="employee-tab-panel" data-tab-panel="login">
        <div class="card" style="margin-top:0;">
            <h2>Login Information</h2>
            <div class="employee-info-grid">
                <p><strong>Login Status:</strong> {{ $employee->user_id ? 'Login Linked' : 'No Login Linked' }}</p>
                <p><strong>Login Email:</strong> {{ $employee->user?->email ?: '-' }}</p>
                <p><strong>Password Reset:</strong> Password reset button not added yet</p>
            </div>

            @if(! $employee->user_id)
                <p style="margin-top:16px;"><a class="btn" href="/admin/employees/{{ $employee->id }}/create-login">Create Employee Login</a></p>
            @endif
        </div>
    </div>

    <div class="employee-tab-panel" data-tab-panel="documents">
        <div class="card" style="margin-top:0;">
            <h2>Documents</h2>
            <div class="employee-info-grid">
                <p><strong>NID Front:</strong> Upload not added yet</p>
                <p><strong>NID Back:</strong> Upload not added yet</p>
                <p><strong>CV:</strong> Upload not added yet</p>
                <p><strong>Appointment Letter:</strong> Upload not added yet</p>
                <p><strong>Agreement:</strong> Upload not added yet</p>
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
    </script>
@endsection
