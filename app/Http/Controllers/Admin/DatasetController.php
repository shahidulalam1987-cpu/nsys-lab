<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Models\BusinessManager;
use App\Models\Client;
use App\Models\ClientPage;
use App\Models\Dataset;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DatasetController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'business_manager_id' => ['nullable', 'exists:business_managers,id'],
            'ad_account_id' => ['nullable', 'exists:ad_accounts,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'status' => ['nullable', Rule::in(array_keys(Dataset::STATUSES))],
            'event_source_type' => ['nullable', Rule::in(array_keys(Dataset::EVENT_SOURCE_TYPES))],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $datasets = Dataset::with(['businessManager', 'adAccount', 'client', 'page'])
            ->withCount('campaigns')
            ->when($filters['business_manager_id'] ?? null, fn ($query, $id) => $query->where('business_manager_id', $id))
            ->when($filters['ad_account_id'] ?? null, fn ($query, $id) => $query->where('ad_account_id', $id))
            ->when($filters['client_id'] ?? null, fn ($query, $id) => $query->where('client_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['event_source_type'] ?? null, fn ($query, $type) => $query->where('event_source_type', $type))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('dataset_name', 'like', '%' . $search . '%')
                        ->orWhere('dataset_id', 'like', '%' . $search . '%')
                        ->orWhere('domain_url', 'like', '%' . $search . '%');
                });
            })
            ->latest()
            ->get();

        return view('admin.datasets.index', array_merge($this->sharedData(), [
            'datasets' => $datasets,
            'filters' => $filters,
            'summary' => [
                'total' => Dataset::count(),
                'active' => Dataset::where('status', 'active')->count(),
                'restricted' => Dataset::where('status', 'restricted')->count(),
                'website' => Dataset::where('event_source_type', 'website')->count(),
            ],
        ]));
    }

    public function create()
    {
        return view('admin.datasets.create', array_merge($this->sharedData(), [
            'dataset' => new Dataset([
                'platform' => 'Meta',
                'event_source_type' => 'website',
                'status' => 'active',
            ]),
        ]));
    }

    public function store(Request $request)
    {
        Dataset::create($this->validatedData($request) + [
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return redirect('/admin/datasets')->with('success', 'Pixel/Dataset saved successfully.');
    }

    public function edit(Dataset $dataset)
    {
        return view('admin.datasets.edit', array_merge($this->sharedData(), compact('dataset')));
    }

    public function update(Request $request, Dataset $dataset)
    {
        $dataset->update($this->validatedData($request, $dataset) + [
            'updated_by' => $request->user()?->id,
        ]);

        return redirect('/admin/datasets')->with('success', 'Pixel/Dataset updated successfully.');
    }

    public function destroy(Dataset $dataset)
    {
        if ($dataset->campaigns()->exists()) {
            return back()->with('error', 'This Pixel/Dataset is linked with campaigns and cannot be deleted.');
        }

        $dataset->delete();

        return redirect('/admin/datasets')->with('success', 'Pixel/Dataset deleted successfully.');
    }

    private function sharedData(): array
    {
        return [
            'businessManagers' => BusinessManager::orderBy('bm_name')->get(),
            'adAccounts' => AdAccount::with(['businessManager', 'client'])->orderBy('ad_account_name')->get(),
            'clients' => Client::orderBy('company_name')->get(),
            'pages' => ClientPage::with(['client', 'businessManager', 'adAccount'])->orderBy('page_name')->get(),
            'eventSourceTypes' => Dataset::EVENT_SOURCE_TYPES,
            'statuses' => Dataset::STATUSES,
        ];
    }

    private function validatedData(Request $request, ?Dataset $dataset = null): array
    {
        $data = $request->validate([
            'dataset_name' => ['required', 'string', 'max:255'],
            'dataset_id' => ['required', 'string', 'max:255', Rule::unique('datasets', 'dataset_id')->ignore($dataset)],
            'business_manager_id' => ['nullable', 'exists:business_managers,id'],
            'ad_account_id' => ['nullable', 'exists:ad_accounts,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'client_page_id' => ['nullable', 'exists:client_pages,id'],
            'platform' => ['required', 'string', 'max:100'],
            'event_source_type' => ['nullable', Rule::in(array_keys(Dataset::EVENT_SOURCE_TYPES))],
            'domain_url' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(Dataset::STATUSES))],
            'notes' => ['nullable', 'string'],
        ]);

        $data['event_source_type'] = $data['event_source_type'] ?? 'website';

        $adAccount = ! empty($data['ad_account_id']) ? AdAccount::find($data['ad_account_id']) : null;
        if ($adAccount && ! empty($data['business_manager_id']) && (int) $adAccount->business_manager_id !== (int) $data['business_manager_id']) {
            throw ValidationException::withMessages(['ad_account_id' => 'Selected ad account does not belong to the selected BM.']);
        }
        if ($adAccount && ! empty($data['client_id']) && $adAccount->client_id && (int) $adAccount->client_id !== (int) $data['client_id']) {
            throw ValidationException::withMessages(['client_id' => 'Selected client is not linked with this ad account.']);
        }

        $page = ! empty($data['client_page_id']) ? ClientPage::find($data['client_page_id']) : null;
        if ($page && ! empty($data['client_id']) && (int) $page->client_id !== (int) $data['client_id']) {
            throw ValidationException::withMessages(['client_page_id' => 'Selected page does not belong to the selected client.']);
        }
        if ($page && ! empty($data['business_manager_id']) && $page->business_manager_id && (int) $page->business_manager_id !== (int) $data['business_manager_id']) {
            throw ValidationException::withMessages(['client_page_id' => 'Selected page is linked with a different BM.']);
        }
        if ($page && ! empty($data['ad_account_id']) && $page->ad_account_id && (int) $page->ad_account_id !== (int) $data['ad_account_id']) {
            throw ValidationException::withMessages(['client_page_id' => 'Selected page is linked with a different ad account.']);
        }

        return $data;
    }
}
