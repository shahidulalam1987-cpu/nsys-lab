@extends('layouts.admin')

@section('content')
    <div>
        <h1>Security Audit</h1>
        <p>Quick admin safety checklist for route protection, file access, and risky actions.</p>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;">
        @foreach($checks as $check)
            @php
                $badgeClass = [
                    'Passed' => 'badge-success',
                    'Warning' => 'badge-warning',
                    'Needs Review' => 'badge-danger',
                ][$check['status']] ?? 'badge-neutral';
            @endphp
            <div class="card" style="margin:0;">
                <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
                    <h3 style="margin:0;">{{ $check['title'] }}</h3>
                    <span class="badge {{ $badgeClass }}">{{ $check['status'] }}</span>
                </div>
                <p style="margin-bottom:0;color:var(--muted);">{{ $check['detail'] }}</p>
            </div>
        @endforeach
    </div>
@endsection
