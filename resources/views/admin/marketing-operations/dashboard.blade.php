@extends('layouts.admin')

@section('content')
    <h1>Agency Operations</h1>
    <p>Central operations center for employee submissions, verification, review, and performance reporting.</p>

    <div class="stats-grid">
        <div class="stat-card"><p>Pending Review</p><h2>{{ number_format($summary['pending']) }}</h2></div>
        <div class="stat-card"><p>Needs Correction</p><h2>{{ number_format($summary['needs_correction']) }}</h2></div>
        <div class="stat-card"><p>Open Issues</p><h2>{{ number_format($summary['open_issues']) }}</h2></div>
        <div class="stat-card"><p>Approved Reports</p><h2>{{ number_format($summary['approved']) }}</h2></div>
    </div>

    <div class="card">
        <h2>Agency Operations Today</h2>
        <div class="stats-grid">
            <div class="stat-card"><p>Today's Orders</p><h2>{{ number_format($enterpriseSummary['today_orders'] ?? 0) }}</h2></div>
            <div class="stat-card"><p>Today's Spend</p><h2>${{ number_format($enterpriseSummary['today_spend'] ?? 0, 2) }}</h2></div>
            <div class="stat-card"><p>Estimated Profit</p><h2>BDT {{ number_format($enterpriseSummary['today_estimated_profit'] ?? 0, 2) }}</h2></div>
            <div class="stat-card"><p>Pending Verifications</p><h2>{{ number_format($enterpriseSummary['pending_verifications'] ?? 0) }}</h2></div>
        </div>
    </div>

    <div class="card">
        <h2>Submission SLA</h2>
        @php
            $submittedTotal = ($enterpriseSummary['on_time_reports'] ?? 0) + ($enterpriseSummary['late_reports'] ?? 0);
            $submissionPercent = $submittedTotal > 0 ? round((($enterpriseSummary['on_time_reports'] ?? 0) / $submittedTotal) * 100, 2) : 0;
        @endphp
        <div class="stats-grid">
            <div class="stat-card"><p>On-time Reports</p><h2>{{ number_format($enterpriseSummary['on_time_reports'] ?? 0) }}</h2></div>
            <div class="stat-card"><p>Late Reports</p><h2>{{ number_format($enterpriseSummary['late_reports'] ?? 0) }}</h2></div>
            <div class="stat-card"><p>Missing Reports</p><h2>{{ number_format(($enterpriseSummary['missing_moderator_reports'] ?? 0) + ($enterpriseSummary['missing_ad_reports'] ?? 0)) }}</h2></div>
            <div class="stat-card"><p>Submission %</p><h2>{{ number_format($submissionPercent, 2) }}%</h2></div>
        </div>
        <p>Moderator and Ad Manager submission windows are controlled from Agency Operations Settings.</p>
    </div>

    <div class="card">
        <h2>Operations Queue</h2>
        <div style="display:flex;flex-wrap:wrap;gap:10px;">
            <a class="btn" href="/admin/marketing-operations/moderator/operations">Moderator Operations</a>
            <a class="btn" href="/admin/marketing-operations/ad-manager/operations">Ad Manager Operations</a>
            <a class="btn" href="/admin/marketing-operations/auditor/operations">Auditor Operations</a>
            <a class="btn" href="/admin/marketing-operations/monitor/operations">Monitor Operations</a>
            <a class="btn" href="/admin/marketing-operations/agency">Agency Review</a>
            <a class="btn" href="/admin/marketing-operations/reports">Reports</a>
            <a class="btn" href="/admin/marketing-operations/settings">Settings</a>
        </div>
    </div>

    <div class="card">
        <h2>Read-only Quality Signals</h2>
        <div class="stats-grid">
            <div class="stat-card"><p>Top Moderator</p><h2>{{ $widgets['top_moderator'] ?: '-' }}</h2></div>
            <div class="stat-card"><p>Top Ad Manager</p><h2>{{ $widgets['top_ad_manager'] ?: '-' }}</h2></div>
            <div class="stat-card"><p>Top Auditor</p><h2>{{ $widgets['top_auditor'] ?: '-' }}</h2></div>
            <div class="stat-card"><p>Top Monitor</p><h2>{{ $widgets['top_monitor'] ?: '-' }}</h2></div>
            <div class="stat-card"><p>Top Trainer</p><h2>{{ $widgets['top_trainer'] ?: '-' }}</h2></div>
            <div class="stat-card"><p>Repeated Mistakes</p><h2>{{ number_format($widgets['repeated_mistakes']) }}</h2></div>
            <div class="stat-card"><p>Training Due</p><h2>{{ number_format($widgets['training_due']) }}</h2></div>
            <div class="stat-card"><p>Average CPP</p><h2>{{ number_format($widgets['average_cpp'], 2) }}</h2></div>
        </div>
    </div>
@endsection
