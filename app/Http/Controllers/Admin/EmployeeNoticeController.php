<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeNotice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeNoticeController extends Controller
{
    public function index()
    {
        return view('admin.employee-notices.index', [
            'notices' => EmployeeNotice::latest('published_at')->latest()->get(),
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
        EmployeeNotice::create($this->validatedData($request) + [
            'created_by' => auth()->id(),
            'published_at' => now(),
        ]);

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

        return redirect('/admin/employee-notices')->with('success', 'Notice updated successfully.');
    }

    public function destroy(EmployeeNotice $notice)
    {
        $notice->delete();

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
