@extends('layouts.admin')

@section('content')
    <h1>Assignment Details</h1>
    <a class="btn" href="/admin/assignments">Back to Assignment Management</a>
    <a class="btn" href="/admin/assignments/{{ $assignment->id }}/edit">Edit Assignment</a>

    <div class="card" style="margin-top:20px;">
        <h2>{{ $assignment->employee?->name }}</h2>
        <p><strong>Employee:</strong> {{ $assignment->employee?->employee_id }} - {{ $assignment->employee?->name }}</p>
        <p><strong>Client:</strong> {{ $assignment->client?->company_name ?: '-' }}</p>
        <p><strong>Page Name:</strong> {{ $assignment->page?->page_name ?: '-' }}</p>
        <p><strong>Campaign:</strong> {{ $assignment->campaign ?: '-' }}</p>
        <p><strong>Page URL:</strong>
            @if($assignment->page?->page_url)
                <a href="{{ $assignment->page->page_url }}" target="_blank">{{ $assignment->page->page_url }}</a>
            @else
                -
            @endif
        </p>
        <p><strong>Shift:</strong> {{ $assignment->shift?->name ?: '-' }}</p>
        <p><strong>Shift Time:</strong> {{ $assignment->shift?->timeRange() ?: '-' }}</p>
        <p><strong>Assigned From:</strong> {{ $assignment->assigned_from?->toDateString() ?: '-' }}</p>
        <p><strong>Assigned To:</strong> {{ $assignment->assigned_to?->toDateString() ?: '-' }}</p>
        <p><strong>Status:</strong> {{ $assignment->statusLabel() }}</p>
        <p><strong>Note:</strong> {{ $assignment->note ?: '-' }}</p>
    </div>
@endsection
