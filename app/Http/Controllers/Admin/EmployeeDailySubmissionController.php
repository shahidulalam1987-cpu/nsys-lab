<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\ClientPage;
use App\Models\EmployeeDailySubmission;
use App\Services\ActivityLogger;
use App\Services\DailyPerformanceMergeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeDailySubmissionController extends Controller
{
    public function index(Request $request, DailyPerformanceMergeService $mergeService)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(EmployeeDailySubmission::STATUSES))],
            'type' => ['nullable', Rule::in(array_keys(EmployeeDailySubmission::TYPES))],
        ]);
        $status = $filters['status'] ?? 'pending';
        $submissions = EmployeeDailySubmission::with([
            'employee', 'client', 'page', 'campaign', 'businessManager', 'adAccount', 'reviewer',
        ])
            ->where('status', $status)
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('submission_type', $type))
            ->latest('submission_date')
            ->latest()
            ->get();
        $submissions->each(function (EmployeeDailySubmission $submission) use ($mergeService) {
            $submission->merge_state = $mergeService->state($submission);
        });
        $counts = EmployeeDailySubmission::selectRaw('status, submission_type, COUNT(*) total')
            ->groupBy('status', 'submission_type')
            ->get();

        return view('admin.employee-submissions.index', compact('submissions', 'filters', 'status', 'counts'));
    }

    public function edit(EmployeeDailySubmission $submission)
    {
        abort_if($submission->status === 'merged', 422, 'Merged submissions cannot be edited.');
        $campaigns = Campaign::with(['client', 'page', 'businessManager', 'adAccount'])->orderBy('campaign_name')->get();
        $pages = ClientPage::with('client')->orderBy('page_name')->get();

        return view('admin.employee-submissions.edit', compact('submission', 'campaigns', 'pages'));
    }

    public function update(Request $request, EmployeeDailySubmission $submission)
    {
        abort_if($submission->status === 'merged', 422, 'Merged submissions cannot be edited.');
        $data = $this->validated($request, $submission->submission_type);
        $selection = $this->resolveSelection($data);
        if ($submission->submission_type === 'order'
            && (int) ($data['confirmed_orders'] ?? 0) + (int) ($data['cancelled_orders'] ?? 0) > (int) $data['orders']) {
            throw ValidationException::withMessages(['orders' => 'Confirmed and cancelled orders cannot exceed total orders.']);
        }
        $submissionKey = EmployeeDailySubmission::duplicateKey(
            $submission->employee_id,
            $data['submission_date'],
            $submission->submission_type,
            $selection['page_id'],
            $selection['campaign_id']
        );
        if (EmployeeDailySubmission::where('submission_key', $submissionKey)->where('id', '!=', $submission->id)->exists()) {
            throw ValidationException::withMessages(['submission_date' => 'A submission already exists for this date, type, page, and campaign.']);
        }
        $submission->update(array_merge($data, $selection, [
            'submission_key' => $submissionKey,
        ]));

        return redirect('/admin/employee-submissions?status='.$submission->status)
            ->with('success', 'Submission updated successfully.');
    }

    public function approve(Request $request, EmployeeDailySubmission $submission)
    {
        DB::transaction(function () use ($submission, $request) {
            $locked = EmployeeDailySubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, ['pending', 'rejected'], true)) {
                throw ValidationException::withMessages(['submission' => 'Only pending or rejected submissions can be approved.']);
            }
            $locked->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'admin_note' => $request->input('admin_note'),
            ]);
            app(ActivityLogger::class)->log('Facebook', 'Employee Submission Approved', 'Submission #'.$submission->id, $request);
        });

        return back()->with('success', 'Submission approved successfully.');
    }

    public function reject(Request $request, EmployeeDailySubmission $submission)
    {
        $data = $request->validate(['admin_note' => ['required', 'string']]);
        DB::transaction(function () use ($submission, $data, $request) {
            $locked = EmployeeDailySubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'merged') {
                throw ValidationException::withMessages(['submission' => 'Merged submissions cannot be rejected.']);
            }
            $locked->update([
                'status' => 'rejected',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'admin_note' => $data['admin_note'],
            ]);
            app(ActivityLogger::class)->log('Facebook', 'Employee Submission Rejected', 'Submission #'.$submission->id, $request);
        });

        return back()->with('success', 'Submission rejected successfully.');
    }

    public function merge(Request $request, EmployeeDailySubmission $submission, DailyPerformanceMergeService $mergeService)
    {
        $data = $request->validate(['replace' => ['nullable', 'boolean']]);
        $report = $mergeService->merge($submission, $request->user(), (bool) ($data['replace'] ?? false));
        app(ActivityLogger::class)->log('Facebook', 'Employee Submissions Merged', 'Daily performance #'.$report->id, $request);

        return redirect('/admin/daily-reports/'.$report->id)->with('success', 'Employee submissions merged into Daily Performance.');
    }

    private function validated(Request $request, string $type): array
    {
        $rules = [
            'submission_date' => ['required', 'date'],
            'page_id' => ['required', 'exists:client_pages,id'],
            'campaign_id' => [$type === 'spend' ? 'required' : 'nullable', 'exists:campaigns,id'],
            'note' => ['nullable', 'string'],
            'admin_note' => ['nullable', 'string'],
        ];
        $rules += $type === 'order'
            ? ['orders' => ['required', 'integer', 'min:0'], 'confirmed_orders' => ['nullable', 'integer', 'min:0'], 'cancelled_orders' => ['nullable', 'integer', 'min:0']]
            : ['dollar_spend' => ['required', 'numeric', 'min:0'], 'cpm' => ['nullable', 'numeric', 'min:0'], 'cpc' => ['nullable', 'numeric', 'min:0'], 'ctr' => ['nullable', 'numeric', 'min:0']];

        return $request->validate($rules);
    }

    private function resolveSelection(array $data): array
    {
        $page = ClientPage::findOrFail($data['page_id']);
        $campaign = ! empty($data['campaign_id']) ? Campaign::findOrFail($data['campaign_id']) : null;
        if ($campaign && ((int) $campaign->client_page_id !== (int) $page->id || (int) $campaign->client_id !== (int) $page->client_id)) {
            throw ValidationException::withMessages(['campaign_id' => 'Selected campaign does not belong to the selected page.']);
        }

        return [
            'client_id' => $page->client_id,
            'page_id' => $page->id,
            'campaign_id' => $campaign?->id,
            'bm_id' => $campaign?->business_manager_id,
            'ad_account_id' => $campaign?->ad_account_id,
        ];
    }
}
