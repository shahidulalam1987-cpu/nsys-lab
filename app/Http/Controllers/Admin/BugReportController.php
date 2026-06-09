<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BugReport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BugReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(BugReport::STATUSES))],
            'priority' => ['nullable', Rule::in(array_keys(BugReport::PRIORITIES))],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $query = BugReport::query()->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
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

    public function store(Request $request)
    {
        BugReport::create($this->validatedData($request) + [
            'bug_id' => $this->nextBugId(),
        ]);

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

    public function update(Request $request, BugReport $bug)
    {
        $bug->update($this->validatedData($request));

        return redirect('/admin/bug-tracker')->with('success', 'Bug updated successfully.');
    }

    public function updateStatus(Request $request, BugReport $bug)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(BugReport::STATUSES))],
        ]);

        $bug->update($data);

        return redirect('/admin/bug-tracker')->with('success', 'Bug status updated successfully.');
    }

    public function destroy(BugReport $bug)
    {
        $bug->delete();

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
