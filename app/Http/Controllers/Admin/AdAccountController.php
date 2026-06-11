<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Models\AdAccountLedger;
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
            'billing_status' => ['nullable', Rule::in(['normal', 'upcoming', 'overdue', 'not_set'])],
            'threshold_status' => ['nullable', Rule::in(['normal', 'warning', 'critical', 'limit_reached'])],
            'balance_status' => ['nullable', Rule::in(['normal', 'low', 'negative'])],
        ]);

        $query = AdAccount::with(['businessManager', 'client'])
            ->when($filters['business_manager_id'] ?? null, fn ($query, $bmId) => $query->where('business_manager_id', $bmId))
            ->when($filters['client_id'] ?? null, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));

        $allAccounts = AdAccount::all();
        $adAccounts = $query->latest()->get()
            ->filter(fn (AdAccount $account) => empty($filters['billing_status']) || $account->billingStatus() === $filters['billing_status'])
            ->filter(fn (AdAccount $account) => empty($filters['threshold_status']) || $account->thresholdStatus() === $filters['threshold_status'])
            ->filter(fn (AdAccount $account) => empty($filters['balance_status']) || $account->balanceStatus() === $filters['balance_status'])
            ->values();

        return view('admin.ad-accounts.index', [
            'adAccounts' => $adAccounts,
            'businessManagers' => BusinessManager::orderBy('bm_name')->get(),
            'clients' => Client::orderBy('company_name')->get(),
            'statuses' => AdAccount::STATUSES,
            'filters' => $filters,
            'summary' => [
                'total' => $allAccounts->count(),
                'active' => $allAccounts->where('status', 'active')->count(),
                'payment_issue' => $allAccounts->where('status', 'payment_issue')->count(),
                'total_threshold' => (float) $allAccounts->sum('threshold_amount'),
                'remaining_threshold' => (float) $allAccounts->sum(fn (AdAccount $account) => $account->remaining_threshold),
                'total_balance' => (float) $allAccounts->sum('current_balance'),
                'near_threshold' => $allAccounts->filter(fn (AdAccount $account) => $account->thresholdStatus() === 'warning')->count(),
                'at_risk' => $allAccounts->filter(fn (AdAccount $account) => $account->thresholdStatus() === 'critical')->count(),
                'limit_reached' => $allAccounts->filter(fn (AdAccount $account) => $account->thresholdStatus() === 'limit_reached')->count(),
                'upcoming_billing' => $allAccounts->filter(fn (AdAccount $account) => $account->billingStatus() === 'upcoming')->count(),
                'overdue_billing' => $allAccounts->filter(fn (AdAccount $account) => $account->billingStatus() === 'overdue')->count(),
                'low_balance' => $allAccounts->filter(fn (AdAccount $account) => $account->balanceStatus() === 'low')->count(),
                'negative_balance' => $allAccounts->filter(fn (AdAccount $account) => $account->balanceStatus() === 'negative')->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.ad-accounts.create', $this->formData(new AdAccount([
            'currency' => AdAccount::CURRENCY,
            'timezone' => 'Asia/Dhaka',
            'status' => 'active',
        ])));
    }

    public function store(Request $request)
    {
        $adAccount = AdAccount::create($this->validatedData($request) + [
            'currency' => AdAccount::CURRENCY,
        ]);

        $this->recordLedger($adAccount, 'threshold_update', (float) $adAccount->threshold_amount, null, (float) $adAccount->threshold_amount, 'Initial threshold amount.');
        $this->recordLedger($adAccount, 'balance_adjustment', (float) $adAccount->current_balance, null, (float) $adAccount->current_balance, 'Initial current balance.');

        return redirect('/admin/ad-accounts/' . $adAccount->id)->with('success', 'Ad account saved successfully.');
    }

    public function show(AdAccount $adAccount)
    {
        return view('admin.ad-accounts.show', [
            'adAccount' => $adAccount->load(['businessManager', 'client', 'pages.client', 'ledgers.creator']),
        ]);
    }

    public function edit(AdAccount $adAccount)
    {
        return view('admin.ad-accounts.edit', $this->formData($adAccount));
    }

    public function update(Request $request, AdAccount $adAccount)
    {
        $before = $adAccount->replicate();
        $data = $this->validatedData($request, $adAccount) + [
            'currency' => AdAccount::CURRENCY,
        ];

        $adAccount->update($data);
        $this->recordFinancialChanges($adAccount, $before);

        return redirect('/admin/ad-accounts/' . $adAccount->id)->with('success', 'Ad account updated successfully.');
    }

    public function ledger(Request $request)
    {
        $filters = $request->validate([
            'ad_account_id' => ['nullable', 'exists:ad_accounts,id'],
            'transaction_type' => ['nullable', Rule::in(array_keys(AdAccountLedger::TRANSACTION_TYPES))],
        ]);

        $query = AdAccountLedger::with(['adAccount', 'creator'])
            ->when($filters['ad_account_id'] ?? null, fn ($query, $accountId) => $query->where('ad_account_id', $accountId))
            ->when($filters['transaction_type'] ?? null, fn ($query, $type) => $query->where('transaction_type', $type))
            ->latest('transaction_date')
            ->latest();

        return view('admin.ad-accounts.ledger', [
            'ledgers' => $query->paginate(30)->withQueryString(),
            'adAccounts' => AdAccount::orderBy('ad_account_name')->get(),
            'transactionTypes' => AdAccountLedger::TRANSACTION_TYPES,
            'filters' => $filters,
        ]);
    }

    public function ledgerShow(AdAccountLedger $ledger)
    {
        return view('admin.ad-accounts.ledger-show', [
            'ledger' => $ledger->load(['adAccount', 'creator']),
        ]);
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

    private function recordFinancialChanges(AdAccount $adAccount, AdAccount $before): void
    {
        if ((float) $before->threshold_amount !== (float) $adAccount->threshold_amount) {
            $this->recordLedger($adAccount, 'threshold_update', (float) $adAccount->threshold_amount - (float) $before->threshold_amount, (float) $before->threshold_amount, (float) $adAccount->threshold_amount, 'Threshold amount updated.');
        }

        if ((float) $before->current_threshold_usage !== (float) $adAccount->current_threshold_usage) {
            $this->recordLedger($adAccount, 'threshold_update', (float) $adAccount->current_threshold_usage - (float) $before->current_threshold_usage, (float) $before->current_threshold_usage, (float) $adAccount->current_threshold_usage, 'Current threshold usage updated.');
        }

        if ((float) $before->current_balance !== (float) $adAccount->current_balance) {
            $type = (float) $adAccount->current_balance >= (float) $before->current_balance ? 'manual_credit' : 'manual_debit';
            $this->recordLedger($adAccount, $type, abs((float) $adAccount->current_balance - (float) $before->current_balance), (float) $before->current_balance, (float) $adAccount->current_balance, 'Current balance adjusted.');
        }

        if ((string) $before->status !== (string) $adAccount->status) {
            $this->recordLedger($adAccount, 'status_change', 0, null, null, 'Status changed from ' . $before->statusLabel() . ' to ' . $adAccount->statusLabel() . '.');
        }

        if ($adAccount->last_payment_date && (! $before->last_payment_date || ! $before->last_payment_date->equalTo($adAccount->last_payment_date))) {
            $this->recordLedger($adAccount, 'billing_paid', 0, null, null, 'Billing paid date updated to ' . $adAccount->last_payment_date->toDateString() . '.');
        }
    }

    private function recordLedger(AdAccount $adAccount, string $type, float $amount, ?float $previousValue, ?float $newValue, ?string $notes = null): void
    {
        $adAccount->ledgers()->create([
            'transaction_date' => now()->toDateString(),
            'transaction_type' => $type,
            'amount' => $amount,
            'previous_value' => $previousValue,
            'new_value' => $newValue,
            'notes' => $notes,
            'created_by' => auth()->id(),
        ]);
    }
}
