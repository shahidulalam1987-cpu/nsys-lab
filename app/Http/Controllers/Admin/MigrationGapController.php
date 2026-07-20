<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Models\AdAccountBillingHistory;
use App\Models\AdAccountCard;
use App\Models\AdAccountPage;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientPage;
use App\Models\Dataset;
use App\Models\FacebookCard;
use App\Models\MetaSpendSnapshot;
use App\Models\MetaSyncLog;
use App\Models\PaymentProvider;
use App\Models\ProviderFeeTracking;
use App\Models\ProviderTransaction;
use App\Models\WhatsAppLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MigrationGapController extends Controller
{
    public function providers()
    {
        return view('admin.migration-gaps.providers', [
            'providers' => PaymentProvider::withCount('transactions')->orderBy('name')->get(),
        ]);
    }

    public function storeProvider(Request $request)
    {
        PaymentProvider::create($request->validate([
            'provider_code' => ['required', 'string', 'max:100', 'unique:payment_providers,provider_code'],
            'name' => ['required', 'string', 'max:255'],
            'provider_type' => ['required', 'string', 'max:100'],
            'currency' => ['required', Rule::in(['USD', 'BDT'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ]));

        return back()->with('success', 'Payment provider saved successfully.');
    }

    public function adAccountPages()
    {
        return view('admin.migration-gaps.ad-account-pages', [
            'mappings' => AdAccountPage::with(['adAccount', 'client', 'page'])->latest()->get(),
            'adAccounts' => AdAccount::orderBy('ad_account_name')->get(),
            'clients' => Client::orderBy('company_name')->get(),
            'pages' => ClientPage::with('client')->orderBy('page_name')->get(),
        ]);
    }

    public function storeAdAccountPage(Request $request)
    {
        AdAccountPage::create($request->validate([
            'ad_account_id' => ['required', 'exists:ad_accounts,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'client_page_id' => ['required', 'exists:client_pages,id'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'mapped_from' => ['nullable', 'date'],
            'mapped_to' => ['nullable', 'date', 'after_or_equal:mapped_from'],
            'notes' => ['nullable', 'string'],
        ]) + [
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Ad account page mapping saved successfully.');
    }

    public function adAccountCards()
    {
        return view('admin.migration-gaps.ad-account-cards', [
            'mappings' => AdAccountCard::with(['adAccount', 'card'])->latest()->get(),
            'adAccounts' => AdAccount::orderBy('ad_account_name')->get(),
            'cards' => FacebookCard::orderBy('card_name')->get(),
        ]);
    }

    public function storeAdAccountCard(Request $request)
    {
        AdAccountCard::create($request->validate([
            'ad_account_id' => ['required', 'exists:ad_accounts,id'],
            'facebook_card_id' => ['required', 'exists:facebook_cards,id'],
            'is_primary' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'mapped_from' => ['nullable', 'date'],
            'mapped_to' => ['nullable', 'date', 'after_or_equal:mapped_from'],
            'notes' => ['nullable', 'string'],
        ]) + [
            'is_primary' => $request->boolean('is_primary'),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Ad account card mapping saved successfully.');
    }

    public function datasets()
    {
        return view('admin.migration-gaps.datasets', [
            'datasets' => Dataset::with(['adAccount', 'client', 'page'])->latest()->get(),
            'adAccounts' => AdAccount::orderBy('ad_account_name')->get(),
            'clients' => Client::orderBy('company_name')->get(),
            'pages' => ClientPage::orderBy('page_name')->get(),
        ]);
    }

    public function storeDataset(Request $request)
    {
        Dataset::create($request->validate([
            'dataset_name' => ['required', 'string', 'max:255'],
            'dataset_id' => ['required', 'string', 'max:255', 'unique:datasets,dataset_id'],
            'ad_account_id' => ['nullable', 'exists:ad_accounts,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'client_page_id' => ['nullable', 'exists:client_pages,id'],
            'platform' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ]) + [
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Dataset saved successfully.');
    }

    public function providerTransactions()
    {
        return view('admin.migration-gaps.provider-transactions', [
            'transactions' => ProviderTransaction::with(['provider', 'card'])->latest('transaction_date')->get(),
            'providers' => PaymentProvider::orderBy('name')->get(),
            'cards' => FacebookCard::orderBy('card_name')->get(),
        ]);
    }

    public function storeProviderTransaction(Request $request)
    {
        ProviderTransaction::create($request->validate([
            'payment_provider_id' => ['nullable', 'exists:payment_providers,id'],
            'facebook_card_id' => ['nullable', 'exists:facebook_cards,id'],
            'transaction_date' => ['required', 'date'],
            'transaction_type' => ['required', 'string', 'max:100'],
            'amount_usd' => ['required', 'numeric'],
            'fee_usd' => ['nullable', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['pending', 'posted', 'failed'])],
            'notes' => ['nullable', 'string'],
        ]) + ['created_by' => $request->user()?->id]);

        return back()->with('success', 'Provider transaction saved successfully.');
    }

    public function providerFees()
    {
        return view('admin.migration-gaps.provider-fees', [
            'fees' => ProviderFeeTracking::with(['provider', 'card'])->latest('sample_date')->get(),
            'providers' => PaymentProvider::orderBy('name')->get(),
            'cards' => FacebookCard::orderBy('card_name')->get(),
        ]);
    }

    public function storeProviderFee(Request $request)
    {
        $data = $request->validate([
            'payment_provider_id' => ['nullable', 'exists:payment_providers,id'],
            'facebook_card_id' => ['nullable', 'exists:facebook_cards,id'],
            'sample_date' => ['required', 'date'],
            'facebook_charge_usd' => ['required', 'numeric', 'min:0.01'],
            'provider_deducted_usd' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);
        $feeAmount = round((float) $data['provider_deducted_usd'] - (float) $data['facebook_charge_usd'], 2);
        $feePercentage = round($feeAmount / max((float) $data['facebook_charge_usd'], 0.01) * 100, 4);

        ProviderFeeTracking::create($data + [
            'fee_amount_usd' => $feeAmount,
            'fee_percentage' => $feePercentage,
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Provider fee sample saved successfully.');
    }

    public function billingHistory()
    {
        return view('admin.migration-gaps.billing-history', [
            'history' => AdAccountBillingHistory::with('adAccount')->latest('billing_date')->get(),
            'adAccounts' => AdAccount::orderBy('ad_account_name')->get(),
        ]);
    }

    public function storeBillingHistory(Request $request)
    {
        AdAccountBillingHistory::create($request->validate([
            'ad_account_id' => ['required', 'exists:ad_accounts,id'],
            'billing_date' => ['required', 'date'],
            'billing_amount_usd' => ['required', 'numeric', 'min:0'],
            'paid_date' => ['nullable', 'date'],
            'payment_status' => ['required', Rule::in(['pending', 'paid', 'overdue'])],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]) + ['created_by' => $request->user()?->id]);

        return back()->with('success', 'Billing history saved successfully.');
    }

    public function metaSnapshots()
    {
        return view('admin.migration-gaps.meta-snapshots', [
            'snapshots' => MetaSpendSnapshot::with('campaign')->latest('snapshot_date')->get(),
            'campaigns' => Campaign::with(['client', 'page', 'adAccount'])->orderBy('campaign_name')->get(),
        ]);
    }

    public function storeMetaSnapshot(Request $request)
    {
        $campaign = Campaign::find($request->input('campaign_id'));

        MetaSpendSnapshot::create($request->validate([
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
            'snapshot_date' => ['required', 'date'],
            'spend_usd' => ['required', 'numeric', 'min:0'],
            'orders' => ['nullable', 'integer', 'min:0'],
            'source' => ['required', 'string', 'max:100'],
        ]) + [
            'ad_account_id' => $campaign?->ad_account_id,
            'client_id' => $campaign?->client_id,
            'client_page_id' => $campaign?->client_page_id,
        ]);

        return back()->with('success', 'Meta spend snapshot saved successfully.');
    }

    public function whatsAppLogs()
    {
        return view('admin.migration-gaps.whatsapp-logs', [
            'logs' => WhatsAppLog::with('client')->latest()->get(),
            'clients' => Client::orderBy('company_name')->get(),
        ]);
    }

    public function storeWhatsAppLog(Request $request)
    {
        WhatsAppLog::create($request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'recipient' => ['nullable', 'string', 'max:255'],
            'message_type' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in(['pending', 'sent', 'failed'])],
            'message' => ['nullable', 'string'],
            'sent_at' => ['nullable', 'date'],
            'response' => ['nullable', 'string'],
        ]) + ['created_by' => $request->user()?->id]);

        return back()->with('success', 'WhatsApp log saved successfully.');
    }

    public function metaSyncLogs()
    {
        return view('admin.migration-gaps.meta-sync-logs', [
            'logs' => MetaSyncLog::latest()->get(),
        ]);
    }

    public function storeMetaSyncLog(Request $request)
    {
        MetaSyncLog::create($request->validate([
            'sync_type' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in(['pending', 'success', 'failed'])],
            'started_at' => ['nullable', 'date'],
            'finished_at' => ['nullable', 'date'],
            'records_processed' => ['nullable', 'integer', 'min:0'],
            'message' => ['nullable', 'string'],
        ]) + ['created_by' => $request->user()?->id]);

        return back()->with('success', 'Meta sync log saved successfully.');
    }
}
