@extends('layouts.admin')

@section('content')
    <h1>Marketing Operations</h1>
    <p>Central operations center for Meta, TikTok, Google Ads, YouTube, and future marketing platforms.</p>

    <div class="stats-grid">
        <div class="stat-card"><p>Pending Review</p><h2>{{ number_format($summary['pending']) }}</h2></div>
        <div class="stat-card"><p>Needs Correction</p><h2>{{ number_format($summary['needs_correction']) }}</h2></div>
        <div class="stat-card"><p>Open Issues</p><h2>{{ number_format($summary['open_issues']) }}</h2></div>
        <div class="stat-card"><p>Approved Reports</p><h2>{{ number_format($summary['approved']) }}</h2></div>
    </div>

    <div class="card">
        <h2>Operations Queue</h2>
        <div style="display:flex;flex-wrap:wrap;gap:10px;">
            <a class="btn" href="/admin/marketing-operations/moderator_order/create">Moderator Report</a>
            <a class="btn" href="/admin/marketing-operations/ad_manager_spend/create">Ad Manager Report</a>
            <a class="btn" href="/admin/marketing-operations/auditor_audit/create">Auditor Report</a>
            <a class="btn" href="/admin/marketing-operations/monitor_issue/create">Monitor Report</a>
            <a class="btn" href="/admin/marketing-operations/trainer_training/create">Trainer Report</a>
            <a class="btn" href="/admin/marketing-operations/management_review/create">Management Report</a>
            <a class="btn" href="/admin/marketing-operations/verification">Performance Verification</a>
            <a class="btn" href="/admin/marketing-operations/reports">Reports</a>
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
