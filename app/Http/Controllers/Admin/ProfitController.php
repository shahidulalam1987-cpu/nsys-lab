<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Models\BusinessManager;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientPage;
use App\Models\DailyPerformanceReport;
use Illuminate\Http\Request;

class ProfitController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'business_manager_id' => ['nullable', 'exists:business_managers,id'],
            'ad_account_id' => ['nullable', 'exists:ad_accounts,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'client_page_id' => ['nullable', 'exists:client_pages,id'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
        ]);

        $reports = DailyPerformanceReport::with(['campaign.businessManager', 'campaign.adAccount', 'campaign.client', 'campaign.page'])
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('report_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('report_date', '<=', $date))
            ->when($filters['campaign_id'] ?? null, fn ($query, $id) => $query->where('campaign_id', $id))
            ->whereHas('campaign', function ($query) use ($filters) {
                $query->when($filters['business_manager_id'] ?? null, fn ($inner, $id) => $inner->where('business_manager_id', $id))
                    ->when($filters['ad_account_id'] ?? null, fn ($inner, $id) => $inner->where('ad_account_id', $id))
                    ->when($filters['client_id'] ?? null, fn ($inner, $id) => $inner->where('client_id', $id))
                    ->when($filters['client_page_id'] ?? null, fn ($inner, $id) => $inner->where('client_page_id', $id));
            })
            ->latest('report_date')
            ->get();

        return view('admin.profit-history', [
            'filters' => $filters,
            'businessManagers' => BusinessManager::orderBy('bm_name')->get(),
            'adAccounts' => AdAccount::orderBy('ad_account_name')->get(),
            'clients' => Client::orderBy('company_name')->get(),
            'clientPages' => ClientPage::orderBy('page_name')->get(),
            'campaigns' => Campaign::orderBy('campaign_name')->get(),
            'summary' => $this->summary($reports),
            'clientRows' => $this->groupRows($reports, fn ($report) => $report->campaign?->client?->company_name ?: 'No Client'),
            'pageRows' => $this->groupRows($reports, fn ($report) => $report->campaign?->page?->page_name ?: 'No Page'),
            'campaignRows' => $this->groupRows($reports, fn ($report) => $report->campaign?->campaign_name ?: 'No Campaign'),
            'bmRows' => $this->groupRows($reports, fn ($report) => $report->campaign?->businessManager?->bm_name ?: 'No BM'),
            'adAccountRows' => $this->groupRows($reports, fn ($report) => $report->campaign?->adAccount?->ad_account_name ?: 'No Ad Account'),
        ]);
    }

    private function summary($reports): array
    {
        $spend = (float) $reports->sum('spend');
        $orders = (int) $reports->sum('orders');

        return [
            'spend' => $spend,
            'orders' => $orders,
            'cost_per_order' => DailyPerformanceReport::costPer($spend, $orders),
        ];
    }

    private function groupRows($reports, callable $labelResolver)
    {
        return $reports
            ->groupBy($labelResolver)
            ->map(function ($items, $label) {
                $summary = $this->summary($items);

                return [
                    'label' => $label,
                    'spend' => $summary['spend'],
                    'orders' => $summary['orders'],
                    'cost_per_order' => $summary['cost_per_order'],
                ];
            })
            ->sortByDesc('spend')
            ->values();
    }
}
