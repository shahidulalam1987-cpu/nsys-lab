@extends('layouts.admin')

@section('content')
    <h1>Payroll Dashboard</h1>
    <p>Current payroll stages and monthly salary totals.</p>

    <div class="stats-grid">
        <a class="stat-card" href="/admin/payroll?status=upcoming" style="text-decoration:none;border-color:#3b82f6;">
            <p>Upcoming Salaries</p><h2>{{ number_format($queueCounts['upcoming']) }}</h2>
        </a>
        <a class="stat-card" href="/admin/payroll?status=due" style="text-decoration:none;border-color:#ef4444;">
            <p>Payroll Action Queue</p><h2>{{ number_format($queueCounts['unpaid']) }}</h2>
        </a>
        <a class="stat-card" href="/admin/payroll?status=due&queue_stage=pending_work_status" style="text-decoration:none;">
            <p>Pending Work Status</p><h2>{{ number_format($queueCounts['pending_work_status']) }}</h2>
        </a>
        <a class="stat-card" href="/admin/payroll?status=due&queue_stage=salary_ready" style="text-decoration:none;">
            <p>Salary Ready</p><h2>{{ number_format($queueCounts['salary_ready']) }}</h2>
        </a>
        <a class="stat-card" href="/admin/payroll?status=due&queue_stage=generated" style="text-decoration:none;border-color:#f59e0b;">
            <p>Pending Approval</p><h2>{{ number_format($queueCounts['pending_approval']) }}</h2>
        </a>
        <a class="stat-card" href="/admin/payroll?status=due&employee_scope=terminated" style="text-decoration:none;border-color:#f59e0b;">
            <p>Final Settlement Due</p><h2>{{ number_format($queueCounts['final_settlement_due']) }}</h2>
        </a>
        <div class="stat-card"><p>Total Generated This Month</p><h2>BDT {{ number_format($summary['total_generated_this_month'], 2) }}</h2></div>
        <div class="stat-card"><p>Total Paid This Month</p><h2>BDT {{ number_format($summary['total_paid_this_month'], 2) }}</h2></div>
    </div>

    <div class="card">
        <h2>Payroll Flow</h2>
        <p>Work Status &rarr; Salary Ready &rarr; Generate Payroll &rarr; Approve &rarr; Confirm Payment &rarr; Salary Report</p>
    </div>
@endsection
