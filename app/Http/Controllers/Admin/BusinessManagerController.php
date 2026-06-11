<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessManager;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BusinessManagerController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(BusinessManager::STATUSES))],
            'verification_status' => ['nullable', Rule::in(array_keys(BusinessManager::VERIFICATION_STATUSES))],
        ]);

        $query = BusinessManager::withCount(['adAccounts', 'pages'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['verification_status'] ?? null, fn ($query, $status) => $query->where('verification_status', $status));

        return view('admin.business-managers.index', [
            'businessManagers' => $query->latest()->get(),
            'statuses' => BusinessManager::STATUSES,
            'verificationStatuses' => BusinessManager::VERIFICATION_STATUSES,
            'filters' => $filters,
            'summary' => [
                'total' => BusinessManager::count(),
                'verified' => BusinessManager::where('verification_status', 'verified')->count(),
                'restricted' => BusinessManager::where('status', 'restricted')->count(),
                'disabled' => BusinessManager::where('status', 'disabled')->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.business-managers.create', [
            'businessManager' => null,
            'statuses' => BusinessManager::STATUSES,
            'verificationStatuses' => BusinessManager::VERIFICATION_STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        BusinessManager::create($this->validatedData($request));

        return redirect('/admin/business-managers')->with('success', 'BM saved successfully.');
    }

    public function show(BusinessManager $businessManager)
    {
        return view('admin.business-managers.show', [
            'businessManager' => $businessManager->load(['adAccounts.client', 'pages.client']),
        ]);
    }

    public function edit(BusinessManager $businessManager)
    {
        return view('admin.business-managers.edit', [
            'businessManager' => $businessManager,
            'statuses' => BusinessManager::STATUSES,
            'verificationStatuses' => BusinessManager::VERIFICATION_STATUSES,
        ]);
    }

    public function update(Request $request, BusinessManager $businessManager)
    {
        $businessManager->update($this->validatedData($request, $businessManager));

        return redirect('/admin/business-managers/' . $businessManager->id)->with('success', 'BM updated successfully.');
    }

    public function destroy(BusinessManager $businessManager)
    {
        if ($businessManager->adAccounts()->exists() || $businessManager->pages()->exists()) {
            return back()->with('success', 'This BM has ad accounts or pages. Remove those records first.');
        }

        $businessManager->delete();

        return redirect('/admin/business-managers')->with('success', 'BM deleted successfully.');
    }

    private function validatedData(Request $request, ?BusinessManager $businessManager = null): array
    {
        return $request->validate([
            'bm_name' => ['required', 'string', 'max:255'],
            'bm_id' => ['required', 'string', 'max:255', Rule::unique('business_managers', 'bm_id')->ignore($businessManager)],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255'],
            'verification_status' => ['required', Rule::in(array_keys(BusinessManager::VERIFICATION_STATUSES))],
            'status' => ['required', Rule::in(array_keys(BusinessManager::STATUSES))],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
