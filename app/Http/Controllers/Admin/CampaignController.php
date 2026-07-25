<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Models\BusinessManager;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientPage;
use App\Models\Dataset;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'business_manager_id' => ['nullable', 'exists:business_managers,id'],
            'ad_account_id' => ['nullable', 'exists:ad_accounts,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'client_page_id' => ['nullable', 'exists:client_pages,id'],
            'objective' => ['nullable', Rule::in(array_keys(Campaign::OBJECTIVES))],
            'status' => ['nullable', Rule::in(array_keys(Campaign::STATUSES))],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $query = Campaign::with(['businessManager', 'adAccount', 'client', 'page', 'dataset'])
            ->when($filters['business_manager_id'] ?? null, fn ($query, $id) => $query->where('business_manager_id', $id))
            ->when($filters['ad_account_id'] ?? null, fn ($query, $id) => $query->where('ad_account_id', $id))
            ->when($filters['client_id'] ?? null, fn ($query, $id) => $query->where('client_id', $id))
            ->when($filters['client_page_id'] ?? null, fn ($query, $id) => $query->where('client_page_id', $id))
            ->when($filters['objective'] ?? null, fn ($query, $objective) => $query->where('objective', $objective))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('start_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('end_date', '<=', $date))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('campaign_name', 'like', '%' . $search . '%')
                        ->orWhere('campaign_id', 'like', '%' . $search . '%')
                        ->orWhereHas('page', fn ($pageQuery) => $pageQuery->where('page_name', 'like', '%' . $search . '%'));
                });
            });

        $allCampaigns = Campaign::all();

        return view('admin.campaigns.index', array_merge($this->sharedData(), [
            'campaigns' => $query->latest()->get(),
            'filters' => $filters,
            'summary' => [
                'total' => $allCampaigns->count(),
                'active' => $allCampaigns->where('status', 'active')->count(),
                'paused' => $allCampaigns->where('status', 'paused')->count(),
                'completed' => $allCampaigns->where('status', 'completed')->count(),
                'archived' => $allCampaigns->where('status', 'archived')->count(),
                'ending_soon' => $allCampaigns->filter(fn (Campaign $campaign) => $campaign->isEndingSoon())->count(),
            ],
        ]));
    }

    public function create()
    {
        return view('admin.campaigns.create', array_merge($this->sharedData(), [
            'campaign' => new Campaign([
                'status' => 'draft',
                'objective' => 'messages',
            ]),
        ]));
    }

    public function store(Request $request)
    {
        $campaign = Campaign::create($this->validatedData($request));

        return redirect('/admin/campaigns/' . $campaign->id)->with('success', 'Campaign saved successfully.');
    }

    public function show(Campaign $campaign)
    {
        $campaign->load(['businessManager', 'adAccount', 'client', 'page', 'dataset', 'dailyPerformanceReports']);
        $reports = $campaign->dailyPerformanceReports->sortByDesc('report_date');
        $totalSpend = (float) $reports->sum('spend');

        return view('admin.campaigns.show', [
            'campaign' => $campaign,
            'performanceReports' => $reports,
            'performanceSummary' => [
                'spend' => $totalSpend,
                'messages' => (int) $reports->sum('messages'),
                'results' => (int) $reports->sum('results'),
                'leads' => (int) $reports->sum('leads'),
                'orders' => (int) $reports->sum('orders'),
                'clicks' => (int) $reports->sum('clicks'),
            ],
        ]);
    }

    public function edit(Campaign $campaign)
    {
        return view('admin.campaigns.edit', array_merge($this->sharedData(), [
            'campaign' => $campaign,
        ]));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $campaign->update($this->validatedData($request, $campaign));

        return redirect('/admin/campaigns/' . $campaign->id)->with('success', 'Campaign updated successfully.');
    }

    public function destroy(Campaign $campaign)
    {
        if ($this->campaignHasOperationalHistory($campaign)) {
            return back()->with('error', 'This campaign has assignments, work status, submissions, performance, or finance history. Archive it instead.');
        }

        $campaign->delete();

        return redirect('/admin/campaigns')->with('success', 'Campaign deleted successfully.');
    }

    private function sharedData(): array
    {
        return [
            'businessManagers' => BusinessManager::orderBy('bm_name')->get(),
            'adAccounts' => AdAccount::with(['businessManager', 'client'])->orderBy('ad_account_name')->get(),
            'clients' => Client::orderBy('company_name')->get(),
            'clientPages' => ClientPage::with(['client', 'businessManager', 'adAccount'])->orderBy('page_name')->get(),
            'datasets' => Dataset::with(['businessManager', 'adAccount', 'client'])->orderBy('dataset_name')->get(),
            'objectives' => Campaign::OBJECTIVES,
            'statuses' => Campaign::STATUSES,
        ];
    }

    private function validatedData(Request $request, ?Campaign $campaign = null): array
    {
        $data = $request->validate([
            'campaign_name' => ['required', 'string', 'max:255'],
            'campaign_id' => ['required', 'string', 'max:255', Rule::unique('campaigns', 'campaign_id')->ignore($campaign)],
            'business_manager_id' => ['required', 'exists:business_managers,id'],
            'ad_account_id' => ['required', 'exists:ad_accounts,id'],
            'client_id' => ['required', 'exists:clients,id'],
            'client_page_id' => ['required', 'exists:client_pages,id'],
            'dataset_id' => ['nullable', 'exists:datasets,id'],
            'objective' => ['required', Rule::in(array_keys(Campaign::OBJECTIVES))],
            'status' => ['required', Rule::in(array_keys(Campaign::STATUSES))],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'daily_budget' => ['nullable', 'numeric', 'min:0'],
            'lifetime_budget' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $adAccount = AdAccount::find($data['ad_account_id']);
        if ((int) $adAccount?->business_manager_id !== (int) $data['business_manager_id']) {
            throw ValidationException::withMessages([
                'ad_account_id' => 'Selected ad account does not belong to the selected BM.',
            ]);
        }

        if ($adAccount?->client_id && (int) $adAccount->client_id !== (int) $data['client_id']) {
            throw ValidationException::withMessages([
                'client_id' => 'Selected client is not linked with this ad account.',
            ]);
        }

        $page = ClientPage::find($data['client_page_id']);
        if ((int) $page?->client_id !== (int) $data['client_id']) {
            throw ValidationException::withMessages([
                'client_page_id' => 'Selected page does not belong to the selected client.',
            ]);
        }

        if ($page?->business_manager_id && (int) $page->business_manager_id !== (int) $data['business_manager_id']) {
            throw ValidationException::withMessages([
                'client_page_id' => 'Selected page is linked with a different BM.',
            ]);
        }

        if ($page?->ad_account_id && (int) $page->ad_account_id !== (int) $data['ad_account_id']) {
            throw ValidationException::withMessages([
                'client_page_id' => 'Selected page is linked with a different ad account.',
            ]);
        }

        $dataset = ! empty($data['dataset_id']) ? Dataset::find($data['dataset_id']) : null;
        if ($dataset && $dataset->business_manager_id && (int) $dataset->business_manager_id !== (int) $data['business_manager_id']) {
            throw ValidationException::withMessages(['dataset_id' => 'Selected Pixel/Dataset is linked with a different BM.']);
        }
        if ($dataset && $dataset->ad_account_id && (int) $dataset->ad_account_id !== (int) $data['ad_account_id']) {
            throw ValidationException::withMessages(['dataset_id' => 'Selected Pixel/Dataset is linked with a different ad account.']);
        }
        if ($dataset && $dataset->client_id && (int) $dataset->client_id !== (int) $data['client_id']) {
            throw ValidationException::withMessages(['dataset_id' => 'Selected Pixel/Dataset is linked with a different client.']);
        }

        $data['daily_budget'] = $data['daily_budget'] ?? 0;
        $data['lifetime_budget'] = $data['lifetime_budget'] ?? 0;

        return $data;
    }

    private function campaignHasOperationalHistory(Campaign $campaign): bool
    {
        return $campaign->assignments()->exists()
            || $campaign->workStatuses()->exists()
            || $campaign->dailyPerformanceReports()->exists()
            || $campaign->employeeSubmissions()->exists()
            || $campaign->marketingOperationsReports()->exists()
            || $campaign->moderatorReports()->exists()
            || $campaign->adManagerReports()->exists()
            || $campaign->operationSummaries()->exists()
            || $campaign->performanceVerifications()->exists()
            || $campaign->cardTransactions()->exists()
            || $campaign->metaSpendSnapshots()->exists();
    }
}
