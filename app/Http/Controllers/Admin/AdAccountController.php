<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Models\BusinessManager;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdAccountController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'business_manager_id' => ['nullable', 'exists:business_managers,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'status' => ['nullable', Rule::in(array_keys(AdAccount::STATUSES))],
        ]);

        $query = AdAccount::with(['businessManager', 'client'])
            ->when($filters['business_manager_id'] ?? null, fn ($query, $bmId) => $query->where('business_manager_id', $bmId))
            ->when($filters['client_id'] ?? null, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));

        $allAccounts = AdAccount::all();

        return view('admin.ad-accounts.index', [
            'adAccounts' => $query->latest()->get(),
            'businessManagers' => BusinessManager::orderBy('bm_name')->get(),
            'clients' => Client::orderBy('company_name')->get(),
            'statuses' => AdAccount::STATUSES,
            'filters' => $filters,
            'summary' => [
                'total' => $allAccounts->count(),
                'active' => $allAccounts->where('status', 'active')->count(),
                'payment_issue' => $allAccounts->where('status', 'payment_issue')->count(),
                'total_threshold' => (float) $allAccounts->sum('threshold_amount'),
                'total_balance' => (float) $allAccounts->sum('current_balance'),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.ad-accounts.create', $this->formData(new AdAccount([
            'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka',
            'status' => 'active',
        ])));
    }

    public function store(Request $request)
    {
        $adAccount = AdAccount::create($this->validatedData($request));

        return redirect('/admin/ad-accounts/' . $adAccount->id)->with('success', 'Ad account saved successfully.');
    }

    public function show(AdAccount $adAccount)
    {
        return view('admin.ad-accounts.show', [
            'adAccount' => $adAccount->load(['businessManager', 'client', 'pages.client']),
        ]);
    }

    public function edit(AdAccount $adAccount)
    {
        return view('admin.ad-accounts.edit', $this->formData($adAccount));
    }

    public function update(Request $request, AdAccount $adAccount)
    {
        $adAccount->update($this->validatedData($request, $adAccount));

        return redirect('/admin/ad-accounts/' . $adAccount->id)->with('success', 'Ad account updated successfully.');
    }

    public function destroy(AdAccount $adAccount)
    {
        if ($adAccount->pages()->exists()) {
            return back()->with('success', 'This ad account has pages. Remove or reassign pages first.');
        }

        $adAccount->delete();

        return redirect('/admin/ad-accounts')->with('success', 'Ad account deleted successfully.');
    }

    private function formData(AdAccount $adAccount): array
    {
        return [
            'adAccount' => $adAccount,
            'businessManagers' => BusinessManager::orderBy('bm_name')->get(),
            'clients' => Client::orderBy('company_name')->get(),
            'statuses' => AdAccount::STATUSES,
        ];
    }

    private function validatedData(Request $request, ?AdAccount $adAccount = null): array
    {
        return $request->validate([
            'ad_account_name' => ['required', 'string', 'max:255'],
            'ad_account_id' => ['required', 'string', 'max:255', Rule::unique('ad_accounts', 'ad_account_id')->ignore($adAccount)],
            'business_manager_id' => ['required', 'exists:business_managers,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'currency' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'max:100'],
            'threshold_amount' => ['required', 'numeric', 'min:0'],
            'current_threshold_usage' => ['required', 'numeric', 'min:0'],
            'current_balance' => ['required', 'numeric'],
            'monthly_billing_date' => ['nullable', 'integer', 'min:1', 'max:31'],
            'last_payment_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'card_last_four' => ['nullable', 'digits:4'],
            'status' => ['required', Rule::in(array_keys(AdAccount::STATUSES))],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
