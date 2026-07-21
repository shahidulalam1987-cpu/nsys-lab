@extends('layouts.admin')

@section('content')
    <style>
        .bonus-header {
            align-items: flex-start;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .bonus-header p {
            margin: 4px 0 0;
        }

        .bonus-actions,
        .bonus-form,
        .bonus-filters,
        .bonus-row-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .bonus-form,
        .bonus-filters {
            align-items: end;
        }

        .bonus-form label,
        .bonus-filters label,
        .bonus-row-actions label {
            color: var(--muted);
            display: grid;
            font-size: 12px;
            font-weight: 700;
            gap: 6px;
        }

        .bonus-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .bonus-table-card {
            overflow-x: auto;
        }

        .bonus-name {
            color: var(--text);
            font-weight: 800;
        }

        .bonus-meta {
            color: var(--muted);
            font-size: 12px;
            margin-top: 3px;
        }

        @media (max-width: 980px) {
            .bonus-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 680px) {
            .bonus-header {
                display: block;
            }

            .bonus-actions {
                margin-top: 12px;
            }

            .bonus-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @php
        $metrics = ['confirmed_orders' => 'Confirmed Orders', 'approved_spend' => 'Approved Spend', 'approval_rate' => 'Approval Rate', 'average_cpo' => 'Average CPO', 'consistency' => 'Consistency'];
        $periods = ['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'];
        $scopeForRule = function ($rule) {
            return match ($rule->applies_to_type) {
                'employee' => ['Employee', $rule->employee_id ? optional($employees->firstWhere('id', $rule->employee_id))->name : null],
                'role' => ['Role', $rule->role_id ? optional($roles->firstWhere('id', $rule->role_id))->name : null],
                default => ['Department', $rule->department_id ? optional($departments->firstWhere('id', $rule->department_id))->name : null],
            };
        };
    @endphp

    <div class="bonus-header">
        <div>
            <h1>Bonus Review</h1>
            <p>Review performance-based bonus rules and earnings. Approved bonuses are not paid automatically.</p>
        </div>
        <div class="bonus-actions">
            <a class="btn" href="/admin/employee-kpi">Employee Performance</a>
            <a class="btn" href="/admin/leaderboard">Leaderboard</a>
            <a class="btn" href="/admin/bonuses/export">Export Earnings</a>
        </div>
    </div>

    <div class="bonus-grid">
        <div class="stat-card">
            <p>Active Rules</p>
            <h2>{{ number_format($summary['active_rules']) }}</h2>
        </div>
        <div class="stat-card">
            <p>Pending Earnings</p>
            <h2>{{ number_format($summary['pending_earnings']) }}</h2>
        </div>
        <div class="stat-card">
            <p>Approved Earnings</p>
            <h2>{{ number_format($summary['approved_earnings']) }}</h2>
        </div>
        <div class="stat-card">
            <p>Total Pending Bonus</p>
            <h2>BDT {{ number_format($summary['pending_bonus'], 2) }}</h2>
        </div>
    </div>

    @if(auth()->user()->hasPermission('bonus.manage'))
        <div class="card">
            <h2>Create Bonus Rule</h2>
            <form class="bonus-form" method="POST" action="/admin/bonuses/rules">
                @csrf
                <label>
                    Rule Name
                    <input name="name" placeholder="Rule Name" required>
                </label>
                <label>
                    Scope Type
                    <select name="applies_to_type">
                        <option value="employee">Employee</option>
                        <option value="role">Role</option>
                        <option value="department">Department</option>
                    </select>
                </label>
                <label>
                    Employee
                    <select name="employee_id">
                        <option value="">Employee Optional</option>
                        @foreach($employees as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Role
                    <select name="role_id">
                        <option value="">Role Optional</option>
                        @foreach($roles as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Department
                    <select name="department_id">
                        <option value="">Department Optional</option>
                        @foreach($departments as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Metric
                    <select name="metric">
                        @foreach($metrics as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Comparison
                    <select name="comparison">
                        <option value="gte">At Least</option>
                        <option value="lte">At Most</option>
                    </select>
                </label>
                <label>
                    Threshold
                    <input type="number" step="0.01" name="threshold" placeholder="Threshold" required>
                </label>
                <label>
                    Bonus Amount
                    <input type="number" step="0.01" name="bonus_amount" placeholder="Bonus Amount" required>
                </label>
                <label>
                    Bonus Type
                    <select name="bonus_type">
                        <option value="fixed">Fixed BDT</option>
                        <option value="percentage">Percentage</option>
                    </select>
                </label>
                <label>
                    Period
                    <select name="period_type">
                        @foreach($periods as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Status
                    <select name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </label>
                <button class="btn">Save Rule</button>
            </form>
        </div>
    @endif

    <div class="card">
        <form class="bonus-filters" method="GET">
            <label>
                Rule Status
                <select name="rule_status">
                    <option value="">All Rules</option>
                    <option value="active" @selected(($filters['rule_status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['rule_status'] ?? '') === 'inactive')>Inactive</option>
                </select>
            </label>
            <label>
                Earning Status
                <select name="earning_status">
                    <option value="">All Earnings</option>
                    <option value="pending" @selected(($filters['earning_status'] ?? '') === 'pending')>Pending</option>
                    <option value="approved" @selected(($filters['earning_status'] ?? '') === 'approved')>Approved</option>
                    <option value="rejected" @selected(($filters['earning_status'] ?? '') === 'rejected')>Rejected</option>
                </select>
            </label>
            <label>
                Employee
                <select name="employee_id">
                    <option value="">All Employees</option>
                    @foreach($employees as $item)
                        <option value="{{ $item->id }}" @selected((string) ($filters['employee_id'] ?? '') === (string) $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Period
                <select name="period_type">
                    <option value="">All Periods</option>
                    @foreach($periods as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['period_type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button class="btn">Filter</button>
            <a class="btn sidebar-muted" href="/admin/bonuses">Reset</a>
        </form>
    </div>

    <div class="card bonus-table-card">
        <h2>Rules</h2>
        <table>
            <thead>
                <tr>
                    <th>Rule</th>
                    <th>Scope</th>
                    <th>Metric</th>
                    <th>Threshold</th>
                    <th>Bonus</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rules as $rule)
                    @php
                        $scope = $scopeForRule($rule);
                        $scopeType = $scope[0];
                        $scopeName = $scope[1] ?: 'Historical '.$scopeType;
                    @endphp
                    <tr>
                        <td>
                            <div class="bonus-name">{{ $rule->name }}</div>
                            <div class="bonus-meta">{{ $periods[$rule->period_type] ?? ucfirst($rule->period_type) }}</div>
                        </td>
                        <td>
                            <span class="badge badge-info">{{ $scopeType }}</span>
                            <div class="bonus-meta">{{ $scopeName }}</div>
                        </td>
                        <td>{{ $metrics[$rule->metric] ?? ucwords(str_replace('_', ' ', $rule->metric)) }}</td>
                        <td>{{ $rule->comparison === 'gte' ? 'At least' : 'At most' }} {{ number_format($rule->threshold, 2) }}</td>
                        <td>{{ $rule->bonus_type === 'fixed' ? 'BDT ' : '' }}{{ number_format($rule->bonus_amount, 2) }}{{ $rule->bonus_type === 'percentage' ? '%' : '' }}</td>
                        <td>
                            <span class="badge {{ $rule->status === 'active' ? 'badge-success' : 'badge-warning' }}">
                                {{ ucfirst($rule->status) }}
                            </span>
                        </td>
                        <td>
                            @if(auth()->user()->hasPermission('bonus.manage'))
                                <form method="POST" action="/admin/bonuses/rules/{{ $rule->id }}/evaluate" onsubmit="return confirm('Evaluate this bonus rule for the current period?')">
                                    @csrf
                                    <button class="btn">Evaluate</button>
                                </form>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">No bonus rules found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card bonus-table-card">
        <h2>Bonus Earnings</h2>
        <p>Approved bonus earnings are reviewed only. They do not create payroll or finance deductions automatically.</p>
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Rule</th>
                    <th>Period</th>
                    <th>Metric</th>
                    <th>Bonus</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($earnings as $earning)
                    <tr>
                        <td>
                            <div class="bonus-name">{{ $earning->employee?->name ?: 'Historical Employee' }}</div>
                            <div class="bonus-meta">{{ $earning->employee?->employee_id }}</div>
                        </td>
                        <td>{{ $earning->rule?->name ?: 'Historical Rule' }}</td>
                        <td>{{ $earning->period_start?->toDateString() }} - {{ $earning->period_end?->toDateString() }}</td>
                        <td>{{ number_format($earning->metric_value, 2) }}</td>
                        <td>BDT {{ number_format($earning->bonus_amount, 2) }}</td>
                        <td>
                            <span class="badge {{ $earning->status === 'approved' ? 'badge-success' : ($earning->status === 'rejected' ? 'badge-danger' : 'badge-warning') }}">
                                {{ ucfirst($earning->status) }}
                            </span>
                        </td>
                        <td>
                            @if($earning->status === 'pending')
                                <div class="bonus-row-actions">
                                    @if(auth()->user()->hasPermission('bonus.approve'))
                                        <form method="POST" action="/admin/bonuses/{{ $earning->id }}/approve" onsubmit="return confirm('Approve this bonus earning?')">
                                            @csrf
                                            <input name="note" placeholder="Approval note">
                                            <button class="btn">Approve</button>
                                        </form>
                                    @endif
                                    @if(auth()->user()->hasPermission('bonus.manage'))
                                        <form method="POST" action="/admin/bonuses/{{ $earning->id }}/reject" onsubmit="return confirm('Reject this bonus earning?')">
                                            @csrf
                                            <input name="note" placeholder="Reject note">
                                            <button class="btn btn-danger">Reject</button>
                                        </form>
                                    @endif
                                </div>
                            @else
                                {{ $earning->note ?: '-' }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">No bonus earnings found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
