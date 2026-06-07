@extends('layouts.admin')

@section('content')
    @php
        $activeStatus = $filters['status'] ?? null;
        $exportQuery = http_build_query($filters ?? []);
        $statusLabels = ['upcoming' => 'Upcoming', 'unpaid' => 'Unpaid', 'partial' => 'Partially Paid', 'paid' => 'Paid'];
    @endphp

    <h1>Salary Generate</h1>

    <p>
        <a class="btn" href="/admin/payroll/create">Generate Salary</a>
        <a class="btn" href="/admin/payroll/export/csv?{{ $exportQuery }}">Export CSV</a>
        <a class="btn" href="/admin/payroll/export/excel?{{ $exportQuery }}">Export Excel</a>
    </p>

    <p>Generate salary based on employee working days for the selected month.</p>

    <div class="card" style="margin-top:20px;">
        <form method="GET" action="/admin/payroll">
            <input type="month" name="month" value="{{ request('month') }}">

            <select name="employee_id">
                <option value="">All Employees</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                        {{ $employee->name }} ({{ $employee->employee_id }})
                    </option>
                @endforeach
            </select>

            <select name="status">
                <option value="">All Status</option>
                @foreach(['upcoming' => 'Upcoming', 'unpaid' => 'Unpaid', 'partial' => 'Partially Paid', 'paid' => 'Paid', 'due' => 'Unpaid / Due'] as $value => $label)
                    <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <button class="btn" type="submit">Filter</button>
            <a href="/admin/payroll">Reset</a>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <p>Total Payable Salary (BDT)</p>
            <h2>BDT {{ number_format($summary['total_payable'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Total Paid Salary</p>
            <h2>BDT {{ number_format($summary['total_paid'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Total Remaining Due</p>
            <h2>BDT {{ number_format($summary['total_due'], 2) }}</h2>
        </div>
        <div class="stat-card">
            <p>Salary Records</p>
            <h2>{{ number_format($summary['record_count'] ?? $payrolls->count()) }}</h2>
        </div>
    </div>

    <div class="card">
        <table>
            @if($activeStatus === 'upcoming')
                <tr>
                    <th>Employee</th>
                    <th>Client</th>
                    <th>Salary Amount</th>
                    <th>Salary Date</th>
                    <th>Days Remaining</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            @elseif($activeStatus === 'paid')
                <tr>
                    <th>Employee</th>
                    <th>Client</th>
                    <th>Amount</th>
                    <th>Paid Date</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Proof</th>
                    <th>Action</th>
                </tr>
            @elseif($activeStatus === 'due')
                <tr>
                    <th>Employee</th>
                    <th>Client</th>
                    <th>Amount Due</th>
                    <th>Salary Date</th>
                    <th>Days Overdue</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            @else
                <tr>
                    <th>Month / Period</th>
                    <th>Employee</th>
                    <th>Client</th>
                    <th>Working Days</th>
                    <th>Payable Salary (BDT)</th>
                    <th>Paid Salary</th>
                    <th>Remaining Due</th>
                    <th>Payment Status</th>
                    <th>Payment Date</th>
                    <th>Proof</th>
                    <th>Action</th>
                </tr>
            @endif
            @forelse($payrolls as $payroll)
                @php
                    $salaryDate = $payroll->employee?->salaryDateForMonth($payroll->salary_month?->copy() ?: now());
                    $remainingDue = max($payroll->payable_salary - $payroll->paid_amount, 0);
                @endphp
                <tr>
                    @if($activeStatus === 'upcoming')
                        <td><a href="/admin/employees/{{ $payroll->employee?->id }}">{{ $payroll->employee?->name ?: '-' }}</a></td>
                        <td>{{ $payroll->client?->company_name ?: '-' }}</td>
                        <td>BDT {{ number_format($remainingDue, 2) }}</td>
                        <td>{{ $salaryDate?->toDateString() ?: '-' }}</td>
                        <td>{{ $salaryDate ? now()->startOfDay()->diffInDays($salaryDate, false) : '-' }}</td>
                        <td>{{ $statusLabels[$payroll->calculated_status] ?? ucfirst($payroll->calculated_status) }}</td>
                    @elseif($activeStatus === 'paid')
                        <td><a href="/admin/employees/{{ $payroll->employee?->id }}">{{ $payroll->employee?->name ?: '-' }}</a></td>
                        <td>{{ $payroll->client?->company_name ?: '-' }}</td>
                        <td>BDT {{ number_format($payroll->paid_amount, 2) }}</td>
                        <td>{{ $payroll->payment_date?->toDateString() ?: '-' }}</td>
                        <td>{{ $payroll->payment_method ?: '-' }}</td>
                        <td>{{ $payroll->transaction_id ?: '-' }}</td>
                        <td>
                            @if($payroll->payment_proof)
                                <a href="/storage/{{ $payroll->payment_proof }}" target="_blank">View Proof</a>
                            @else
                                -
                            @endif
                        </td>
                    @elseif($activeStatus === 'due')
                        <td><a href="/admin/employees/{{ $payroll->employee?->id }}">{{ $payroll->employee?->name ?: '-' }}</a></td>
                        <td>{{ $payroll->client?->company_name ?: '-' }}</td>
                        <td>BDT {{ number_format($remainingDue, 2) }}</td>
                        <td>{{ $salaryDate?->toDateString() ?: '-' }}</td>
                        <td>{{ $salaryDate ? max(now()->startOfDay()->diffInDays($salaryDate, false) * -1, 0) : '-' }}</td>
                        <td>{{ $statusLabels[$payroll->calculated_status] ?? ucfirst($payroll->calculated_status) }}</td>
                    @else
                        <td>{{ $payroll->salary_period }}</td>
                        <td>
                            @if($payroll->employee)
                                <a href="/admin/employees/{{ $payroll->employee->id }}">{{ $payroll->employee->name }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $payroll->client?->company_name ?: '-' }}</td>
                        <td>{{ $payroll->working_days ?? '-' }}</td>
                        <td>BDT {{ number_format($payroll->payable_salary, 2) }}</td>
                        <td>BDT {{ number_format($payroll->paid_amount, 2) }}</td>
                        <td>BDT {{ number_format($remainingDue, 2) }}</td>
                        <td>{{ $statusLabels[$payroll->calculated_status] ?? ucfirst($payroll->calculated_status) }}</td>
                        <td>{{ $payroll->payment_date?->toDateString() ?: '-' }}</td>
                        <td>
                            @if($payroll->payment_proof)
                                <a href="/storage/{{ $payroll->payment_proof }}" target="_blank">View Proof</a>
                            @else
                                -
                            @endif
                        </td>
                    @endif
                    <td>
                        <a href="/admin/payroll/{{ $payroll->id }}">View</a>
                        |
                        <a href="/admin/payroll/{{ $payroll->id }}/edit">Edit</a>
                        |
                        <form method="POST" action="/admin/payroll/{{ $payroll->id }}/delete" style="display:inline;">
                            @csrf
                            <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this salary record?');">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="11">No salary records found.</td></tr>
            @endforelse
        </table>
    </div>

    @if(($cycleEmployees ?? collect())->isNotEmpty())
        <div class="card">
            <h2>Salary Cycle Employees</h2>
            <p>Employees shown here match the selected salary cycle status but do not have a generated salary record for this cycle yet.</p>

            <table>
                <tr>
                    <th>Employee</th>
                    <th>Client</th>
                    <th>Salary Day</th>
                    <th>Salary Date</th>
                    <th>Amount Due</th>
                    <th>Current Salary Status</th>
                    <th>Action</th>
                </tr>
                @foreach($cycleEmployees as $cycleEmployee)
                    @php
                        $cycleSalaryDate = request('status') === 'due'
                            ? $cycleEmployee->currentSalaryDueDate()
                            : $cycleEmployee->nextSalaryDate();
                    @endphp
                    <tr>
                        <td>
                            <a href="/admin/employees/{{ $cycleEmployee->id }}">{{ $cycleEmployee->employee_id }}</a><br>
                            {{ $cycleEmployee->name }}
                        </td>
                        <td>{{ $cycleEmployee->activeAssignments->first()?->client?->company_name ?: '-' }}</td>
                        <td>{{ $cycleEmployee->salaryCycleDay() ?: '-' }}</td>
                        <td>{{ $cycleSalaryDate?->toDateString() ?: '-' }}</td>
                        <td>BDT {{ number_format($cycleEmployee->monthly_salary, 2) }}</td>
                        <td>{{ $cycleEmployee->salaryStatusLabel() }}</td>
                        <td><a class="btn" href="/admin/payroll/create">Generate Salary</a></td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif
@endsection
