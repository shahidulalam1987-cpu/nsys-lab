<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BugReport;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BugReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(BugReport::STATUSES))],
            'priority' => ['nullable', Rule::in(array_keys(BugReport::PRIORITIES))],
            'module' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $query = BugReport::query()->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($query) use ($filters) {
                $query->where('bug_id', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('module', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('title', 'like', '%' . $filters['search'] . '%');
            });
        }

        return view('admin.bug-tracker.index', [
            'bugs' => $query->paginate(20)->withQueryString(),
            'priorities' => BugReport::PRIORITIES,
            'statuses' => BugReport::STATUSES,
            'filters' => $filters,
            'modules' => BugReport::select('module')->distinct()->orderBy('module')->pluck('module'),
            'statusCounts' => BugReport::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function create()
    {
        return view('admin.bug-tracker.create', [
            'bug' => new BugReport([
                'priority' => 'medium',
                'status' => 'open',
                'reported_by' => auth()->user()?->name,
            ]),
            'priorities' => BugReport::PRIORITIES,
            'statuses' => BugReport::STATUSES,
        ]);
    }

    public function store(Request $request, ActivityLogger $logger)
    {
        $bug = BugReport::create($this->validatedData($request) + [
            'bug_id' => $this->nextBugId(),
        ]);

        $logger->log('Bug Tracker', 'Bug Created', $bug->bug_id . ' - ' . $bug->title, $request);

        return redirect('/admin/bug-tracker')->with('success', 'Bug added successfully.');
    }

    public function edit(BugReport $bug)
    {
        return view('admin.bug-tracker.edit', [
            'bug' => $bug,
            'priorities' => BugReport::PRIORITIES,
            'statuses' => BugReport::STATUSES,
        ]);
    }

    public function update(Request $request, BugReport $bug, ActivityLogger $logger)
    {
        $bug->update($this->validatedData($request));

        $logger->log('Bug Tracker', 'Bug Updated', $bug->bug_id . ' - ' . $bug->title, $request);

        return redirect('/admin/bug-tracker')->with('success', 'Bug updated successfully.');
    }

    public function updateStatus(Request $request, BugReport $bug, ActivityLogger $logger)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(BugReport::STATUSES))],
        ]);

        $bug->update($data);

        $action = $data['status'] === 'closed' ? 'Bug Closed' : 'Bug Updated';
        $logger->log('Bug Tracker', $action, $bug->bug_id . ' status changed to ' . $bug->statusLabel(), $request);

        return redirect('/admin/bug-tracker')->with('success', 'Bug status updated successfully.');
    }

    public function destroy(Request $request, BugReport $bug, ActivityLogger $logger)
    {
        $description = $bug->bug_id . ' - ' . $bug->title;
        $bug->delete();

        $logger->log('Bug Tracker', 'Bug Deleted', $description, $request);

        return redirect('/admin/bug-tracker')->with('success', 'Bug deleted successfully.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'module' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::in(array_keys(BugReport::PRIORITIES))],
            'status' => ['required', Rule::in(array_keys(BugReport::STATUSES))],
            'reported_by' => ['nullable', 'string', 'max:120'],
            'assigned_to' => ['nullable', 'string', 'max:120'],
            'fixed_note' => ['nullable', 'string'],
        ]);
    }

    private function nextBugId(): string
    {
        $lastBug = BugReport::orderByDesc('id')->first();
        $nextNumber = $lastBug ? ((int) str_replace('BUG-', '', $lastBug->bug_id)) + 1 : 1;

        do {
            $bugId = 'BUG-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (BugReport::where('bug_id', $bugId)->exists());

        return $bugId;
    }
}
