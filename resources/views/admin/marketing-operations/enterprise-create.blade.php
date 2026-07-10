@extends('layouts.admin')

@section('content')
    <h1>Add {{ $moduleLabel }} Report</h1>
    <p>Submit daily operational data. Approved reports are locked for audit safety.</p>

    <div class="card">
        <form method="POST" action="/admin/marketing-operations/{{ $module }}/operations" enctype="multipart/form-data" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
            @csrf
            <label>Client<br><select name="client_id" @if($module !== 'monitor') required @endif><option value="">Select Client</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->company_name }}</option>@endforeach</select></label>
            @if($module !== 'monitor')
                <label>Page<br><select name="page_id" @if($module !== 'ad-manager') required @endif><option value="">Select Page</option>@foreach($pages as $page)<option value="{{ $page->id }}">{{ $page->page_name }}</option>@endforeach</select></label>
            @endif
            @if(in_array($module, ['moderator', 'ad-manager'], true))
                <label>Campaign<br><select name="campaign_id" @if($module === 'ad-manager') required @endif><option value="">Select Campaign</option>@foreach($campaigns as $campaign)<option value="{{ $campaign->id }}">{{ $campaign->campaign_name }}</option>@endforeach</select></label>
            @endif
            <label>Status<br><select name="status"><option value="submitted">Submitted</option><option value="draft">Draft</option></select></label>

            @if($module === 'moderator')
                <label>Submission Date<br><input type="date" name="submission_date" value="{{ today()->toDateString() }}" required></label>
                <label>Orders<br><input type="number" name="orders" min="0" required></label>
                <label>Confirmed Orders<br><input type="number" name="confirmed_orders" min="0" required></label>
                <label>Cancelled Orders<br><input type="number" name="cancelled_orders" min="0" required></label>
                <label>Pending Orders<br><input type="number" name="pending_orders" min="0" required></label>
                <label>Returned Orders<br><input type="number" name="returned_orders" min="0" value="0"></label>
                <label>Attachment<br><input type="file" name="attachment"></label>
            @elseif($module === 'ad-manager')
                <label>Date<br><input type="date" name="report_date" value="{{ today()->toDateString() }}" required></label>
                <label>Spend USD<br><input type="number" step="0.01" name="spend_usd" min="0" required></label>
                <label>Spend BDT<br><input type="number" step="0.01" name="spend_bdt" min="0"></label>
                <label>Purchases<br><input type="number" name="purchases" min="0" required></label>
                <label>CPM<br><input type="number" step="0.01" name="cpm" min="0"></label>
                <label>CTR<br><input type="number" step="0.0001" name="ctr" min="0"></label>
                <label>CPC<br><input type="number" step="0.01" name="cpc" min="0"></label>
                <label>Frequency<br><input type="number" step="0.01" name="frequency" min="0"></label>
                <label>Reach<br><input type="number" name="reach" min="0"></label>
                <label>Impressions<br><input type="number" name="impressions" min="0"></label>
                <label>Attachment<br><input type="file" name="attachment"></label>
            @elseif($module === 'auditor')
                <label>Moderator<br><select name="moderator_id"><option value="">Select Moderator</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->name }}</option>@endforeach</select></label>
                <label>Audit Date<br><input type="date" name="audit_date" value="{{ today()->toDateString() }}" required></label>
                <label>Average Response Time<br><input type="number" step="0.01" name="average_response_time" min="0" required></label>
                <label>Longest Delay<br><input type="number" step="0.01" name="longest_delay" min="0" required></label>
                <label>Total Delayed Replies<br><input type="number" name="total_delayed_replies" min="0" required></label>
                <label>QA Score<br><input type="number" step="0.01" name="qa_score" min="0" max="100" required></label>
                <label>Message Quality<br><input type="number" step="0.01" name="message_quality" min="0" max="100" required></label>
                <label>Greeting Score<br><input type="number" step="0.01" name="greeting_score" min="0" max="100" required></label>
                <label>Closing Score<br><input type="number" step="0.01" name="closing_score" min="0" max="100" required></label>
                <label>Follow-up Score<br><input type="number" step="0.01" name="follow_up_score" min="0" max="100" required></label>
                <label>Overall Status<br><select name="overall_status" required>@foreach(['excellent','good','average','poor','critical'] as $status)<option value="{{ $status }}">{{ ucfirst($status) }}</option>@endforeach</select></label>
                <label>Screenshot<br><input type="file" name="screenshot"></label>
                <label style="grid-column:1/-1;">Remarks<br><textarea name="remarks"></textarea></label>
            @else
                <label>Employee<br><select name="employee_id" required><option value="">Select Employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->name }}</option>@endforeach</select></label>
                <label>Department<br><select name="department_id"><option value="">Select Department</option>@foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select></label>
                <label>Page<br><select name="page_id"><option value="">Select Page</option>@foreach($pages as $page)<option value="{{ $page->id }}">{{ $page->page_name }}</option>@endforeach</select></label>
                <label>Review Date<br><input type="date" name="review_date" value="{{ today()->toDateString() }}" required></label>
                <label>Issue Type<br><input type="text" name="issue_type" required></label>
                <label>Severity<br><select name="severity" required>@foreach(['low','medium','high','critical'] as $severity)<option value="{{ $severity }}">{{ ucfirst($severity) }}</option>@endforeach</select></label>
                <label>Resolution<br><select name="resolution_status" required>@foreach(['pending','resolved','escalated'] as $status)<option value="{{ $status }}">{{ ucfirst($status) }}</option>@endforeach</select></label>
                <label>Screenshot<br><input type="file" name="screenshot"></label>
                <label style="grid-column:1/-1;">Description<br><textarea name="description" required></textarea></label>
                <label style="grid-column:1/-1;">Recommendation<br><textarea name="recommendation"></textarea></label>
            @endif

            <label style="grid-column:1/-1;">Notes<br><textarea name="notes"></textarea></label>
            <div style="grid-column:1/-1;"><button class="btn" type="submit">Save Report</button> <a class="btn" href="/admin/marketing-operations/{{ $module }}/operations">Cancel</a></div>
        </form>
    </div>
@endsection
