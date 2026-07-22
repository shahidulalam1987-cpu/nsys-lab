@extends('layouts.admin')

@section('content')
    <style>
        .security-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .security-check-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 14px;
        }

        .security-card {
            margin: 0;
        }

        .security-card-header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
        }

        .security-risk-list {
            color: var(--muted);
            margin: 10px 0 0;
            padding-left: 18px;
        }
    </style>

    <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <h1>Security Audit</h1>
            <p>Quick admin safety checklist for route protection, file access, and risky actions.</p>
        </div>
        <span class="badge badge-neutral">Last Checked: {{ $lastCheckedAt->format('Y-m-d H:i') }}</span>
    </div>

    <div class="security-summary-grid">
        <div class="stat-card"><p>Passed</p><h2>{{ number_format((int) ($summary['Passed'] ?? 0)) }}</h2></div>
        <div class="stat-card"><p>Warning</p><h2>{{ number_format((int) ($summary['Warning'] ?? 0)) }}</h2></div>
        <div class="stat-card"><p>Needs Review</p><h2>{{ number_format((int) ($summary['Needs Review'] ?? 0)) }}</h2></div>
    </div>

    <div class="security-check-grid">
        @foreach($checks as $check)
            @php
                $badgeClass = [
                    'Passed' => 'badge-success',
                    'Warning' => 'badge-warning',
                    'Needs Review' => 'badge-warning',
                ][$check['status']] ?? 'badge-neutral';
            @endphp
            <div class="card security-card">
                <div class="security-card-header">
                    <h3 style="margin:0;">{{ $check['title'] }}</h3>
                    <span class="badge {{ $badgeClass }}">{{ $check['status'] }}</span>
                </div>
                <p style="margin-bottom:0;color:var(--muted);">{{ $check['detail'] }}</p>
                @if($check['title'] === 'Pending risky GET actions' && $riskyGetRoutes->isNotEmpty())
                    <ul class="security-risk-list">
                        @foreach($riskyGetRoutes as $route)
                            <li>{{ $route }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
@endsection
