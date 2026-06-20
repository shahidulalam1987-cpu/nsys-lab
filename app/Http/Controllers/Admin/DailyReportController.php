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
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DailyReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $query = $this->filteredQuery($filters)->latest('report_date')->latest();
        $reports = $query->get();

        return view('admin.daily-reports.index', array_merge($this->sharedData(), [
            'reports' => $reports,
            'filters' => $filters,
            'summary' => [
                'spend' => (float) $reports->sum('spend'),
                'messages' => (int) $reports->sum('messages'),
                'results' => (int) $reports->sum('results'),
                'leads' => (int) $reports->sum('leads'),
                'orders' => (int) $reports->sum('orders'),
                'cost_per_order' => DailyPerformanceReport::costPer((float) $reports->sum('spend'), (int) $reports->sum('orders')),
                'revenue' => (float) $reports->sum(fn (DailyPerformanceReport $report) => $report->clientRevenue()),
                'profit' => (float) $reports->sum(fn (DailyPerformanceReport $report) => $report->profit()),
            ],
        ]));
    }

    public function create()
    {
        return view('admin.daily-reports.create', array_merge($this->sharedData(), [
            'dailyReport' => new DailyPerformanceReport([
                'report_date' => now()->toDateString(),
            ]),
        ]));
    }

    public function store(Request $request)
    {
        if ($request->input('entry_mode') === 'bulk') {
            return $this->storeBulk($request);
        }

        $data = $this->validatedData($request);
        $existing = DailyPerformanceReport::where('campaign_id', $data['campaign_id'])
            ->whereDate('report_date', $data['report_date'])
            ->first();

        if ($existing && ! $request->boolean('update_existing')) {
            throw ValidationException::withMessages([
                'campaign_id' => 'Performance already exists for this campaign and date. Tick Update Existing to replace it.',
            ]);
        }

        $report = $existing ?: new DailyPerformanceReport();
        $report->fill($data)->save();

        return redirect('/admin/daily-reports/' . $report->id)
            ->with('success', $existing ? 'Existing performance report updated successfully.' : 'Daily performance saved successfully.');
    }

    public function show(DailyPerformanceReport $dailyReport)
    {
        $this->authorizeModeratorCampaign($dailyReport->campaign_id);

        return view('admin.daily-reports.show', [
            'dailyReport' => $dailyReport->load(['campaign.businessManager', 'campaign.adAccount', 'campaign.client', 'campaign.page']),
        ]);
    }

    public function edit(DailyPerformanceReport $dailyReport)
    {
        $this->authorizeModeratorCampaign($dailyReport->campaign_id);

        return view('admin.daily-reports.edit', array_merge($this->sharedData(), [
            'dailyReport' => $dailyReport->load('campaign'),
        ]));
    }

    public function update(Request $request, DailyPerformanceReport $dailyReport)
    {
        $this->authorizeModeratorCampaign($dailyReport->campaign_id);
        $data = $this->validatedData($request, $dailyReport);
        $duplicate = DailyPerformanceReport::where('campaign_id', $data['campaign_id'])
            ->whereDate('report_date', $data['report_date'])
            ->whereKeyNot($dailyReport->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'campaign_id' => 'Another performance report already exists for this campaign and date.',
            ]);
        }

        $dailyReport->update($data);

        return redirect('/admin/daily-reports/' . $dailyReport->id)->with('success', 'Daily performance updated successfully.');
    }

    public function destroy(DailyPerformanceReport $dailyReport)
    {
        $this->authorizeModeratorCampaign($dailyReport->campaign_id);
        $dailyReport->delete();

        return redirect('/admin/daily-reports')->with('success', 'Daily performance deleted successfully.');
    }

    private function storeBulk(Request $request)
    {
        $request->validate([
            'bulk_report_date' => ['required', 'date'],
            'bulk_rows' => ['required', 'array'],
            'update_existing' => ['nullable', 'boolean'],
        ]);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $date = $request->input('bulk_report_date');

        foreach ($request->input('bulk_rows', []) as $row) {
            if (empty($row['enabled'])) {
                continue;
            }

            $data = validator($row + ['report_date' => $date], [
                'campaign_id' => ['required', 'exists:campaigns,id'],
                'report_date' => ['required', 'date'],
                'spend' => ['required', 'numeric', 'min:0'],
                'card_provider' => ['nullable', 'string', 'max:255'],
                'fee_usd' => ['nullable', 'numeric', 'min:0'],
                'extra_charge_usd' => ['nullable', 'numeric', 'min:0'],
                'messages' => ['nullable', 'integer', 'min:0'],
                'results' => ['nullable', 'integer', 'min:0'],
                'leads' => ['nullable', 'integer', 'min:0'],
                'orders' => ['required', 'integer', 'min:0'],
                'reach' => ['nullable', 'integer', 'min:0'],
                'impressions' => ['nullable', 'integer', 'min:0'],
                'clicks' => ['nullable', 'integer', 'min:0'],
                'notes' => ['nullable', 'string'],
            ])->validate();
            $this->authorizeModeratorCampaign((int) $data['campaign_id']);

            $existing = DailyPerformanceReport::where('campaign_id', $data['campaign_id'])
                ->whereDate('report_date', $date)
                ->first();

            if ($existing && ! $request->boolean('update_existing')) {
                $skipped++;
                continue;
            }

            $report = $existing ?: new DailyPerformanceReport();
            $report->fill([
                'campaign_id' => $data['campaign_id'],
                'report_date' => $date,
                'spend' => $data['spend'],
                'card_provider' => $data['card_provider'] ?? null,
                'fee_usd' => $data['fee_usd'] ?? 0,
                'extra_charge_usd' => $data['extra_charge_usd'] ?? 0,
                'messages' => $data['messages'] ?? 0,
                'results' => $data['results'] ?? 0,
                'leads' => $data['leads'] ?? 0,
                'orders' => $data['orders'],
                'reach' => $data['reach'] ?? 0,
                'impressions' => $data['impressions'] ?? 0,
                'clicks' => $data['clicks'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ])->save();

            $existing ? $updated++ : $created++;
        }

        return redirect('/admin/daily-reports')
            ->with('success', "Bulk performance saved. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}.");
    }

    private function sharedData(): array
    {
        $campaignIds = $this->moderatorCampaignIds();
        $campaigns = Campaign::with(['businessManager', 'adAccount', 'client', 'page'])
            ->when($campaignIds !== null, fn ($query) => $query->whereIn('id', $campaignIds))
            ->orderBy('campaign_name')
            ->get();

        return [
            'businessManagers' => BusinessManager::when($campaignIds !== null, fn ($query) => $query->whereIn('id', $campaigns->pluck('business_manager_id')))->orderBy('bm_name')->get(),
            'adAccounts' => AdAccount::when($campaignIds !== null, fn ($query) => $query->whereIn('id', $campaigns->pluck('ad_account_id')))->orderBy('ad_account_name')->get(),
            'clients' => Client::when($campaignIds !== null, fn ($query) => $query->whereIn('id', $campaigns->pluck('client_id')))->orderBy('company_name')->get(),
            'clientPages' => ClientPage::when($campaignIds !== null, fn ($query) => $query->whereIn('id', $campaigns->pluck('client_page_id')))->orderBy('page_name')->get(),
            'campaigns' => $campaigns,
            'campaignStatuses' => Campaign::STATUSES,
        ];
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'business_manager_id' => ['nullable', 'exists:business_managers,id'],
            'ad_account_id' => ['nullable', 'exists:ad_accounts,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'client_page_id' => ['nullable', 'exists:client_pages,id'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
            'campaign_status' => ['nullable', Rule::in(array_keys(Campaign::STATUSES))],
        ]);
    }

    private function filteredQuery(array $filters)
    {
        return DailyPerformanceReport::with(['campaign.businessManager', 'campaign.adAccount', 'campaign.client', 'campaign.page'])
            ->when($this->moderatorCampaignIds() !== null, fn ($query) => $query->whereIn('campaign_id', $this->moderatorCampaignIds()))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('report_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('report_date', '<=', $date))
            ->when($filters['campaign_id'] ?? null, fn ($query, $id) => $query->where('campaign_id', $id))
            ->whereHas('campaign', function ($query) use ($filters) {
                $query->when($filters['business_manager_id'] ?? null, fn ($inner, $id) => $inner->where('business_manager_id', $id))
                    ->when($filters['ad_account_id'] ?? null, fn ($inner, $id) => $inner->where('ad_account_id', $id))
                    ->when($filters['client_id'] ?? null, fn ($inner, $id) => $inner->where('client_id', $id))
                    ->when($filters['client_page_id'] ?? null, fn ($inner, $id) => $inner->where('client_page_id', $id))
                    ->when($filters['campaign_status'] ?? null, fn ($inner, $status) => $inner->where('status', $status));
            });
    }

    private function validatedData(Request $request, ?DailyPerformanceReport $dailyReport = null): array
    {
        $data = $request->validate([
            'campaign_id' => ['required', 'exists:campaigns,id'],
            'report_date' => ['required', 'date'],
            'spend' => ['required', 'numeric', 'min:0'],
            'card_provider' => ['nullable', 'string', 'max:255'],
            'fee_usd' => ['nullable', 'numeric', 'min:0'],
            'extra_charge_usd' => ['nullable', 'numeric', 'min:0'],
            'messages' => ['nullable', 'integer', 'min:0'],
            'results' => ['nullable', 'integer', 'min:0'],
            'leads' => ['nullable', 'integer', 'min:0'],
            'orders' => ['required', 'integer', 'min:0'],
            'reach' => ['nullable', 'integer', 'min:0'],
            'impressions' => ['nullable', 'integer', 'min:0'],
            'clicks' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);
        $this->authorizeModeratorCampaign((int) $data['campaign_id']);

        return array_merge([
            'card_provider' => null,
            'fee_usd' => 0,
            'extra_charge_usd' => 0,
            'messages' => 0,
            'results' => 0,
            'leads' => 0,
            'reach' => 0,
            'impressions' => 0,
            'clicks' => 0,
        ], $data);
    }

    private function moderatorCampaignIds(): ?array
    {
        if (! auth()->user()?->hasRole('moderator')) {
            return null;
        }

        $employee = auth()->user()->employee;
        abort_unless($employee, 403, 'Moderator account is not linked to an employee.');

        return $employee->activeAssignments()->whereNotNull('campaign_id')->pluck('campaign_id')->unique()->values()->all();
    }

    private function authorizeModeratorCampaign(?int $campaignId): void
    {
        $campaignIds = $this->moderatorCampaignIds();
        if ($campaignIds !== null) {
            abort_unless($campaignId && in_array($campaignId, $campaignIds, true), 403);
        }
    }
}
