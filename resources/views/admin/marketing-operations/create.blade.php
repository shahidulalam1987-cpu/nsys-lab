@extends('layouts.admin')

@php($typeLabel = \App\Models\MarketingOperationsReport::REPORT_TYPES[$type] ?? 'Marketing Report')

@section('content')
    <h1>{{ $typeLabel }}</h1>
    <p>Submit a {{ strtolower($typeLabel) }} for admin review.</p>

    @if($errors->any())
        <div class="card" style="border-color:#ef4444;color:#fecaca;">
            <strong>Report was not saved.</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card">
        <form method="POST" action="/admin/marketing-operations/{{ $type }}" enctype="multipart/form-data" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
            @csrf
            <label>Date<br><input type="date" name="report_date" value="{{ old('report_date', now()->toDateString()) }}" required></label>
            <label>Platform<br>
                <select name="platform">
                    @foreach(\App\Models\MarketingOperationsReport::PLATFORMS as $platform)
                        <option value="{{ $platform }}" @selected(old('platform', 'Meta') === $platform)>{{ $platform }}</option>
                    @endforeach
                </select>
            </label>
            <label>Client<br><select name="client_id"><option value="">Select Client</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->company_name }}</option>@endforeach</select></label>
            <label>Page<br><select name="page_id"><option value="">Select Page</option>@foreach($pages as $page)<option value="{{ $page->id }}">{{ $page->page_name }} - {{ $page->client?->company_name }}</option>@endforeach</select></label>
            <label>Campaign<br><select name="campaign_id"><option value="">Select Campaign</option>@foreach($campaigns as $campaign)<option value="{{ $campaign->id }}">{{ $campaign->campaign_name }}</option>@endforeach</select></label>
            <label>Ad Account<br><select name="ad_account_id"><option value="">Select Ad Account</option>@foreach($adAccounts as $account)<option value="{{ $account->id }}">{{ $account->ad_account_name }}</option>@endforeach</select></label>
            <label>Employee<br><select name="target_employee_id"><option value="">Select Employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_id }} - {{ $employee->name }}</option>@endforeach</select></label>
            <label>Department<br><select name="department_id"><option value="">Select Department</option>@foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select></label>
            <label>Role<br><select name="role_id"><option value="">Select Role</option>@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach</select></label>

            @if($type === 'moderator_order')
                <label>Confirmed Orders<br><input type="number" name="confirmed_orders" min="0" value="0" required></label>
                <label>Cancelled Orders<br><input type="number" name="cancelled_orders" min="0" value="0" required></label>
                <label>Pending Orders<br><input type="number" name="pending_orders" min="0" value="0" required></label>
                <label>Returned Orders<br><input type="number" name="returned_orders" min="0" value="0"></label>
                <label>Replacement Orders<br><input type="number" name="replacement_orders" min="0" value="0"></label>
            @elseif($type === 'ad_manager_spend')
                <label>Dollar Spend<br><input type="number" step="0.01" name="dollar_spend" min="0" required></label>
                <label>Currency<br><input type="text" name="currency" value="USD"></label>
                <label>Cost Per Purchase<br><input type="number" step="0.01" name="cost_per_purchase" min="0" required></label>
                <label>Impressions<br><input type="number" name="impressions" min="0"></label>
                <label>Clicks<br><input type="number" name="clicks" min="0"></label>
                <label>CTR<br><input type="number" step="0.0001" name="ctr" min="0"></label>
                <label>CPM<br><input type="number" step="0.01" name="cpm" min="0"></label>
                <label>CPC<br><input type="number" step="0.01" name="cpc" min="0"></label>
            @elseif($type === 'auditor_audit')
                <label>Average Response Time<br><input type="number" step="0.01" name="average_response_time" min="0" required></label>
                <label>Maximum Delay<br><input type="number" step="0.01" name="maximum_delay" min="0" required></label>
                <label>Missed Messages<br><input type="number" name="missed_messages" min="0" required></label>
                <label>Delayed Conversations<br><input type="number" name="delayed_conversations" min="0" required></label>
                <label>Wrong Replies<br><input type="number" name="wrong_replies" min="0" required></label>
                <label>Follow-up Quality<br><input type="number" name="follow_up_quality" min="0" max="100" required></label>
                <label>Response Quality<br><input type="number" name="response_quality" min="0" max="100" required></label>
                <label>Customer Handling<br><input type="number" name="customer_handling" min="0" max="100" required></label>
                <label>Issue Severity<br><select name="severity" required><option>Low</option><option>Medium</option><option>High</option></select></label>
            @elseif($type === 'monitor_issue')
                <label>Mistake Category<br><select name="mistake_category" required>@foreach(['Wrong Reply','Late Reply','Wrong Information','Poor Follow-up','Missing Report','SOP Violation','Order Mistake','Ad Spend Mistake','Customer Complaint','Other'] as $item)<option>{{ $item }}</option>@endforeach</select></label>
                <label>Severity<br><select name="severity" required><option>Low</option><option>Medium</option><option>High</option></select></label>
                <label style="grid-column:1/-1;">Correction Suggestion<br><textarea name="correction_suggestion" rows="3" required></textarea></label>
            @elseif($type === 'trainer_training')
                <label>Training Type<br><select name="training_type" required>@foreach(['Onboarding','SOP Training','Message Handling','Customer Follow-up','Sales Training','Ad Reporting Training','Policy Training','Re-training','Performance Improvement'] as $item)<option>{{ $item }}</option>@endforeach</select></label>
                <label>Score<br><input type="number" step="0.01" name="score" min="0" max="100"></label>
                <label>Pass/Fail<br><select name="pass_fail"><option value="">Not Set</option><option>Pass</option><option>Fail</option></select></label>
                <label>Next Training Date<br><input type="date" name="next_training_date"></label>
                <label style="grid-column:1/-1;">Observation<br><textarea name="observation" rows="2"></textarea></label>
                <label style="grid-column:1/-1;">Improvement Needed<br><textarea name="improvement_needed" rows="2"></textarea></label>
            @elseif($type === 'management_review')
                <label>Operations Status<br><select name="operations_status" required><option>Good</option><option>Warning</option><option>Critical</option></select></label>
                <label style="grid-column:1/-1;">Daily Summary<br><textarea name="daily_summary" rows="3" required></textarea></label>
                @foreach(['today_issues' => "Today's Issues", 'resolved_issues' => 'Resolved Issues', 'pending_issues' => 'Pending Issues', 'high_priority_issues' => 'High Priority Issues', 'escalations' => 'Escalations', 'recommendations' => 'Recommendations'] as $field => $label)
                    <label style="grid-column:1/-1;">{{ $label }}<br><textarea name="{{ $field }}" rows="2"></textarea></label>
                @endforeach
            @endif

            <label>Screenshot<br><input type="file" name="screenshot" accept=".jpg,.jpeg,.png,.webp,.pdf"></label>
            <label>Attachment<br><input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xlsx,.zip"></label>
            <label style="grid-column:1/-1;">Notes<br><textarea name="notes" rows="3"></textarea></label>
            <div style="grid-column:1/-1;"><button class="btn" type="submit">Submit Report</button> <a class="btn" href="/admin/marketing-operations">Cancel</a></div>
        </form>
    </div>
@endsection
