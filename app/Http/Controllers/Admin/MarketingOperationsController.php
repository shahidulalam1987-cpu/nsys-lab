<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientPage;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeRole;
use App\Models\EmployeeDailySubmission;
use App\Models\MarketingOperationsReport;
use App\Services\MarketingOperationsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarketingOperationsController extends Controller
{
    public function dashboard(MarketingOperationsService $service)
    {
        $reports = MarketingOperationsReport::query();

        return view('admin.marketing-operations.dashboard', [
            'summary' => [
                'pending' => (clone $reports)->where('status', 'pending')->count(),
                'needs_correction' => (clone $reports)->where('status', 'needs_correction')->count(),
                'open_issues' => (clone $reports)->where('status', 'open')->count(),
                'approved' => (clone $reports)->where('status', 'approved')->count(),
            ],
            'widgets' => $service->widgets(),
        ]);
    }

    public function index(Request $request, MarketingOperationsService $service)
    {
        $filters = $request->validate([
            'report_type' => ['nullable', Rule::in(array_keys(MarketingOperationsReport::REPORT_TYPES))],
            'platform' => ['nullable', Rule::in(MarketingOperationsReport::PLATFORMS)],
            'status' => ['nullable', Rule::in(array_keys(MarketingOperationsReport::STATUSES))],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'page_id' => ['nullable', 'exists:client_pages,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
        ]);

        return view('admin.marketing-operations.index', [
            'reports' => $service->query($filters, $request->user())->paginate(30)->withQueryString(),
            'filters' => $filters,
            'clients' => Client::orderBy('company_name')->get(),
            'pages' => ClientPage::orderBy('page_name')->get(),
            'employees' => Employee::orderBy('name')->get(),
        ]);
    }

    public function create(string $type)
    {
        abort_unless(isset(MarketingOperationsReport::REPORT_TYPES[$type]), 404);

        return view('admin.marketing-operations.create', $this->formData($type));
    }

    public function store(Request $request, string $type, MarketingOperationsService $service)
    {
        abort_unless($service->canSubmit($request->user(), $type), 403);
        $service->store($request, $type, $request->user());

        return redirect('/admin/marketing-operations/reports?report_type=' . $type)
            ->with('success', 'Marketing operations report submitted.');
    }

    public function verification(Request $request, MarketingOperationsService $service)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'string'],
        ]);

        return view('admin.marketing-operations.verification', [
            'groups' => $service->verificationGroups($filters),
            'filters' => $filters,
        ]);
    }

    public function updateStatus(Request $request, MarketingOperationsReport $report, MarketingOperationsService $service)
    {
        abort_unless($service->canManage($request->user()), 403);
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected', 'needs_correction', 'fixed', 'repeated', 'closed', 'merged'])],
            'admin_note' => ['nullable', 'string'],
        ]);

        $service->updateStatus($report, $data['status'], $data['admin_note'] ?? null, $request->user());

        return back()->with('success', 'Marketing report status updated.');
    }

    public function reports(Request $request, MarketingOperationsService $service)
    {
        return $this->index($request, $service);
    }

    private function formData(string $type): array
    {
        return [
            'type' => $type,
            'clients' => Client::orderBy('company_name')->get(),
            'pages' => ClientPage::with('client')->orderBy('page_name')->get(),
            'campaigns' => Campaign::with(['client', 'page'])->orderBy('campaign_name')->get(),
            'adAccounts' => AdAccount::orderBy('ad_account_name')->get(),
            'employees' => Employee::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'roles' => EmployeeRole::orderBy('name')->get(),
        ];
    }
}
