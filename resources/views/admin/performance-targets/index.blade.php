@extends('layouts.admin')

@section('content')
    <style>
        .target-header {
            align-items: flex-start;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .target-header p {
            margin: 4px 0 0;
        }

        .target-actions,
        .target-form,
        .target-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .target-form,
        .target-filters {
            align-items: end;
        }

        .target-form label,
        .target-filters label {
            color: var(--muted);
            display: grid;
            font-size: 12px;
            font-weight: 700;
            gap: 6px;
        }

        .target-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .target-table-card {
            overflow-x: auto;
        }

        .target-scope {
            color: var(--text);
            font-weight: 800;
        }

        .target-meta {
            color: var(--muted);
            font-size: 12px;
            margin-top: 3px;
        }

        @media (max-width: 980px) {
            .target-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 680px) {
            .target-header {
                display: block;
            }

            .target-actions {
                margin-top: 12px;
            }

            .target-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @php
        $targetTypes = ['orders' => 'Orders', 'spend' => 'Spend', 'max_cpo' => 'Maximum CPO', 'approval_rate' => 'Minimum Approval Rate'];
        $periods = ['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'];
        $scopeFor = function ($target) {
            if ($target->employee_id) {
                return ['Employee', $target->employee?->name ?: 'Historical Employee'];
            }
            if ($target->role_id) {
                return ['Role', $target->role?->name ?: 'Historical Role'];
            }
            return ['Department', $target->department?->name ?: 'Historical Department'];
        };
    @endphp

    <div class="target-header">
        <div>
            <h1>Performance Targets</h1>
            <p>Set measurable employee, role, or department targets for performance tracking.</p>
        </div>
        <div class="target-actions">
            <a class="btn" href="/admin/employee-kpi">Employee Performance</a>
            <a class="btn" href="/admin/leaderboard">Leaderboard</a>
        </div>
    </div>

    <div class="target-grid">
        <div class="stat-card">
            <p>Active Targets</p>
            <h2>{{ number_format($summary['active']) }}</h2>
        </div>
        <div class="stat-card">
            <p>Employee Targets</p>
            <h2>{{ number_format($summary['employee']) }}</h2>
        </div>
        <div class="stat-card">
            <p>Role Targets</p>
            <h2>{{ number_format($summary['role']) }}</h2>
        </div>
        <div class="stat-card">
            <p>Department Targets</p>
            <h2>{{ number_format($summary['department']) }}</h2>
        </div>
    </div>

    <div class="card">
        <h2>Create Target</h2>
        <form class="target-form" method="POST" action="/admin/performance-targets">
            @csrf
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
                Target Type
                <select name="target_type">
                    @foreach($targetTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Target Value
                <input type="number" step="0.01" name="target_value" placeholder="Target Value" required>
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
                Start Date
                <input type="date" name="start_date" value="{{ today()->toDateString() }}" required>
            </label>
            <label>
                End Date
                <input type="date" name="end_date">
            </label>
            <label>
                Status
                <select name="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </label>
            <button class="btn">Save Target</button>
        </form>
    </div>

    <div class="card">
        <form class="target-filters" method="GET">
            <label>
                Scope
                <select name="scope">
                    <option value="">All Scopes</option>
                    <option value="employee" @selected(($filters['scope'] ?? '') === 'employee')>Employee</option>
                    <option value="role" @selected(($filters['scope'] ?? '') === 'role')>Role</option>
                    <option value="department" @selected(($filters['scope'] ?? '') === 'department')>Department</option>
                </select>
            </label>
            <label>
                Target Type
                <select name="target_type">
                    <option value="">All Types</option>
                    @foreach($targetTypes as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['target_type'] ?? '') === $value)>{{ $label }}</option>
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
            <label>
                Status
                <select name="status">
                    <option value="">All Status</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                </select>
            </label>
            <button class="btn">Filter</button>
            <a class="btn sidebar-muted" href="/admin/performance-targets">Reset</a>
        </form>
    </div>

    <div class="card target-table-card">
        <table>
            <thead>
                <tr>
                    <th>Scope</th>
                    <th>Target</th>
                    <th>Period</th>
                    <th>Dates</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($targets as $target)
                    @php
                        $scope = $scopeFor($target);
                        $scopeType = $scope[0];
                        $scopeName = $scope[1];
                    @endphp
                    <tr>
                        <td>
                            <span class="badge badge-info">{{ $scopeType }}</span>
                            <div class="target-scope">{{ $scopeName }}</div>
                        </td>
                        <td>
                            <strong>{{ $targetTypes[$target->target_type] ?? ucwords(str_replace('_', ' ', $target->target_type)) }}</strong>
                            <div class="target-meta">Value: {{ number_format($target->target_value, 2) }}</div>
                        </td>
                        <td>{{ $periods[$target->period_type] ?? ucfirst($target->period_type) }}</td>
                        <td>{{ $target->start_date?->toDateString() }} - {{ $target->end_date?->toDateString() ?: 'Open' }}</td>
                        <td>
                            <span class="badge {{ $target->status === 'active' ? 'badge-success' : 'badge-warning' }}">
                                {{ ucfirst($target->status) }}
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="/admin/performance-targets/{{ $target->id }}" onsubmit="return confirm('Delete this performance target?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No performance targets found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
