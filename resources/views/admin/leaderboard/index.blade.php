@extends('layouts.admin')

@section('content')
    <style>
        .leaderboard-header {
            align-items: flex-start;
            display: flex;
            gap: 14px;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .leaderboard-header p {
            margin: 4px 0 0;
        }

        .leaderboard-actions,
        .leaderboard-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .leaderboard-filters {
            align-items: end;
        }

        .leaderboard-filters label {
            color: var(--muted);
            display: grid;
            font-size: 12px;
            font-weight: 700;
            gap: 6px;
        }

        .leaderboard-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .leaderboard-table-card {
            overflow-x: auto;
        }

        .rank-pill {
            background: rgba(66, 232, 255, .14);
            border: 1px solid rgba(66, 232, 255, .28);
            border-radius: 999px;
            color: var(--cyan);
            display: inline-block;
            font-weight: 800;
            min-width: 42px;
            padding: 5px 9px;
            text-align: center;
        }

        .leaderboard-employee {
            color: var(--text);
            font-weight: 800;
        }

        .leaderboard-meta {
            color: var(--muted);
            font-size: 12px;
            margin-top: 3px;
        }

        @media (max-width: 980px) {
            .leaderboard-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 680px) {
            .leaderboard-header {
                display: block;
            }

            .leaderboard-actions {
                margin-top: 12px;
            }

            .leaderboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @php
        $formatMetric = function ($value) use ($type) {
            return match ($type) {
                'spend' => 'USD '.number_format($value, 2),
                'cpo' => 'USD '.number_format($value, 2),
                'approval', 'consistency' => number_format($value, 2).'%',
                default => number_format($value, 2),
            };
        };
    @endphp

    <div class="leaderboard-header">
        <div>
            <h1>Employee Leaderboard</h1>
            <p>{{ $summary['metric_label'] }} ranking from {{ $from->toDateString() }} to {{ $to->toDateString() }}.</p>
        </div>
        <div class="leaderboard-actions">
            <a class="btn" href="/admin/employee-kpi">Employee Performance</a>
            <a class="btn" href="/admin/leaderboard/export?{{ http_build_query($filters) }}">Export CSV</a>
        </div>
    </div>

    <div class="leaderboard-grid">
        <div class="stat-card">
            <p>Ranked Employees</p>
            <h2>{{ number_format($summary['ranked_employees']) }}</h2>
        </div>
        <div class="stat-card">
            <p>Selected Metric</p>
            <h2>{{ $summary['metric_label'] }}</h2>
        </div>
        <div class="stat-card">
            <p>Top Performer</p>
            <h2>{{ $summary['top_employee']['employee']->name ?? '-' }}</h2>
        </div>
        <div class="stat-card">
            <p>Top Value</p>
            <h2>{{ $formatMetric($summary['top_value']) }}</h2>
        </div>
    </div>

    <div class="card">
        <form class="leaderboard-filters" method="GET">
            <label>
                Ranking
                <select name="type">
                    @foreach($metricLabels as $value => $label)
                        <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                From
                <input type="date" name="date_from" value="{{ $from->toDateString() }}">
            </label>
            <label>
                To
                <input type="date" name="date_to" value="{{ $to->toDateString() }}">
            </label>
            <label>
                Department
                <select name="department_id">
                    <option value="">All Departments</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) ($filters['department_id'] ?? '') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Client
                <select name="client_id">
                    <option value="">All Clients</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" @selected((string) ($filters['client_id'] ?? '') === (string) $client->id)>{{ $client->company_name }}</option>
                    @endforeach
                </select>
            </label>
            <button class="btn">Apply</button>
            <a class="btn sidebar-muted" href="/admin/leaderboard">Reset</a>
        </form>
    </div>

    <div class="card leaderboard-table-card">
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Employee</th>
                    <th>{{ $summary['metric_label'] }}</th>
                    <th>Approval</th>
                    <th>Active / Missing</th>
                    <th>Trend</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td><span class="rank-pill">#{{ $loop->iteration }}</span></td>
                        <td>
                            <div class="leaderboard-employee">{{ $row['employee']->name }}</div>
                            <div class="leaderboard-meta">{{ $row['employee']->roleName() }} | {{ $row['employee']->departmentName() }}</div>
                        </td>
                        <td>{{ $formatMetric($row['metric_value']) }}</td>
                        <td>{{ number_format($row['approval_rate'], 2) }}%</td>
                        <td>{{ number_format($row['active_days']) }} / {{ number_format($row['missing_days']) }}</td>
                        <td>
                            <span class="badge {{ $row['consistency'] >= 80 ? 'badge-success' : 'badge-warning' }}">
                                {{ $row['consistency'] >= 80 ? 'Stable' : 'Needs Attention' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No leaderboard data found for the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
