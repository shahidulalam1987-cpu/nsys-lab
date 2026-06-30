@extends('layouts.admin')

@section('content')
    <h1>Edit Employee Submission</h1>
    <a class="btn" href="/admin/employee-submissions?status={{ $submission->status }}">Back</a>
    <div class="card" style="margin-top:20px; max-width:780px;">
        <form method="POST" action="/admin/employee-submissions/{{ $submission->id }}">
            @csrf @method('PUT')
            <p>Employee<br><input type="text" value="{{ $submission->employee?->name }}" readonly></p>
            <p>Date<br><input type="date" name="submission_date" value="{{ old('submission_date', $submission->submission_date?->toDateString()) }}" required></p>
            <p>Page<br><select name="page_id" required>@foreach($pages as $page)<option value="{{ $page->id }}" {{ (int) old('page_id', $submission->page_id) === $page->id ? 'selected' : '' }}>{{ $page->page_name }} - {{ $page->client?->company_name }}</option>@endforeach</select></p>
            <p>Campaign<br><select name="campaign_id" {{ $submission->submission_type === 'spend' ? 'required' : '' }}><option value="">No Campaign</option>@foreach($campaigns as $campaign)<option value="{{ $campaign->id }}" {{ (int) old('campaign_id', $submission->campaign_id) === $campaign->id ? 'selected' : '' }}>{{ $campaign->campaign_name }}</option>@endforeach</select></p>
            @if($submission->submission_type === 'order')
                <p>Total Orders<br><input type="number" name="orders" min="0" value="{{ old('orders', $submission->orders) }}" required></p>
                <p>Confirmed Orders<br><input type="number" name="confirmed_orders" min="0" value="{{ old('confirmed_orders', $submission->confirmed_orders) }}"></p>
                <p>Cancelled Orders<br><input type="number" name="cancelled_orders" min="0" value="{{ old('cancelled_orders', $submission->cancelled_orders) }}"></p>
            @else
                <p>Dollar Spend<br><input type="number" step="0.01" min="0" name="dollar_spend" value="{{ old('dollar_spend', $submission->dollar_spend) }}" required></p>
                <p>CPM<br><input type="number" step="0.01" min="0" name="cpm" value="{{ old('cpm', $submission->cpm) }}"></p>
                <p>CPC<br><input type="number" step="0.01" min="0" name="cpc" value="{{ old('cpc', $submission->cpc) }}"></p>
                <p>CTR<br><input type="number" step="0.0001" min="0" name="ctr" value="{{ old('ctr', $submission->ctr) }}"></p>
            @endif
            <p>Employee Note<br><textarea name="note">{{ old('note', $submission->note) }}</textarea></p>
            <p>Admin Note<br><textarea name="admin_note">{{ old('admin_note', $submission->admin_note) }}</textarea></p>
            <button class="btn" type="submit">Update Submission</button>
        </form>
    </div>
@endsection
