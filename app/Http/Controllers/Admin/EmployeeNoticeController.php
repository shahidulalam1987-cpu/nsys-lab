<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeNotice;
use App\Models\EmployeeNoticeRead;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeNoticeController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'category' => ['nullable', Rule::in(array_keys(EmployeeNotice::CATEGORIES))],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $query = EmployeeNotice::withCount('reads')
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('published_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('published_at', '<=', $date))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            });

        $summaryQuery = clone $query;
        $readCount = EmployeeNoticeRead::whereIn('employee_notice_id', (clone $summaryQuery)->select('id'))->count();

        return view('admin.employee-notices.index', [
            'notices' => $query->latest('published_at')->latest()->paginate(25)->withQueryString(),
            'categories' => EmployeeNotice::CATEGORIES,
            'filters' => $filters,
            'summary' => [
                'total' => (clone $summaryQuery)->count(),
                'salary' => (clone $summaryQuery)->where('category', 'salary')->count(),
                'emergency' => (clone $summaryQuery)->where('category', 'emergency')->count(),
                'reads' => $readCount,
            ],
        ]);
    }

    public function create()
    {
        return view('admin.employee-notices.create', [
            'notice' => null,
            'categories' => EmployeeNotice::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        $notice = EmployeeNotice::create($this->validatedData($request) + [
            'created_by' => auth()->id(),
            'published_at' => now(),
        ]);

        app(ActivityLogger::class)->log('Notice Board', 'Notice Created', 'Notice #' . $notice->id . ' published: ' . $notice->title, $request);

        return redirect('/admin/employee-notices')->with('success', 'Notice published successfully.');
    }

    public function edit(EmployeeNotice $notice)
    {
        return view('admin.employee-notices.edit', [
            'notice' => $notice,
            'categories' => EmployeeNotice::CATEGORIES,
        ]);
    }

    public function update(Request $request, EmployeeNotice $notice)
    {
        $notice->update($this->validatedData($request));

        app(ActivityLogger::class)->log('Notice Board', 'Notice Updated', 'Notice #' . $notice->id . ' updated: ' . $notice->title, $request);

        return redirect('/admin/employee-notices')->with('success', 'Notice updated successfully.');
    }

    public function destroy(EmployeeNotice $notice)
    {
        $description = 'Notice #' . $notice->id . ' deleted: ' . $notice->title;
        $notice->delete();

        app(ActivityLogger::class)->log('Notice Board', 'Notice Deleted', $description, request());

        return redirect('/admin/employee-notices')->with('success', 'Notice deleted successfully.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(EmployeeNotice::CATEGORIES))],
            'description' => ['required', 'string'],
        ]);
    }
}
