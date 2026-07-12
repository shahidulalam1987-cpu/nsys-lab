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
use App\Services\EnterpriseMarketingOperationsService;
use App\Services\MarketingOperationsService;
use App\Services\MarketingOperationsSettingsService;
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
            'enterpriseSummary' => app(EnterpriseMarketingOperationsService::class)->dashboard(),
            'operationSettings' => app(MarketingOperationsSettingsService::class)->all(),
        ]);
    }

    public function agency(Request $request, EnterpriseMarketingOperationsService $service)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        return view('admin.marketing-operations.agency', [
            'summary' => $service->dashboard(),
            'summaries' => $service->pageDailySummaries($filters),
            'filters' => $filters,
        ]);
    }

    public function settings(MarketingOperationsSettingsService $settings)
    {
        return view('admin.marketing-operations.settings', [
            'modules' => EnterpriseMarketingOperationsService::MODULES,
            'settings' => $settings->all(),
            'labels' => MarketingOperationsSettingsService::LABELS,
        ]);
    }

    public function updateSettings(Request $request, MarketingOperationsSettingsService $settings)
    {
        abort_unless($request->user()->isSuperAdmin() || $request->user()->hasPermission('agency_operations.manage'), 403);

        $data = $request->validate([
            'timezone' => ['required', 'timezone'],
            'moderator_submission_start' => ['required', 'date_format:H:i'],
            'moderator_submission_end' => ['required', 'date_format:H:i'],
            'ad_manager_submission_start' => ['required', 'date_format:H:i'],
            'ad_manager_submission_end' => ['required', 'date_format:H:i'],
            'auditor_review_start' => ['required', 'date_format:H:i'],
            'auditor_review_end' => ['required', 'date_format:H:i'],
            'monitor_review_start' => ['required', 'date_format:H:i'],
            'monitor_review_end' => ['required', 'date_format:H:i'],
            'agency_review_start' => ['required', 'date_format:H:i'],
            'agency_review_end' => ['required', 'date_format:H:i'],
            'late_submission_buffer_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'missing_report_buffer_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'reminder_before_open_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'reminder_before_close_minutes' => ['required', 'regex:/^\d+(,\d+)*$/'],
        ]);

        $settings->update($data, $request->user());

        return back()->with('success', 'Marketing operations settings updated.');
    }

    public function enterpriseIndex(Request $request, string $module, EnterpriseMarketingOperationsService $service)
    {
        abort_unless(isset(EnterpriseMarketingOperationsService::MODULES[$module]), 404);
        $filters = $request->validate([
            'status' => ['nullable', 'string'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'page_id' => ['nullable', 'exists:client_pages,id'],
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        return view('admin.marketing-operations.enterprise-index', [
            'module' => $module,
            'moduleLabel' => EnterpriseMarketingOperationsService::MODULES[$module],
            'reports' => $service->query($module, $filters, $request->user())->paginate(30)->withQueryString(),
            'filters' => $filters,
            'dateColumn' => $service->dateColumn($module),
            'clients' => Client::orderBy('company_name')->get(),
            'pages' => ClientPage::orderBy('page_name')->get(),
            'campaigns' => Campaign::orderBy('campaign_name')->get(),
            'employees' => Employee::orderBy('name')->get(),
            'canManage' => $service->canManage($request->user()),
        ]);
    }

    public function enterpriseCreate(string $module)
    {
        abort_unless(isset(EnterpriseMarketingOperationsService::MODULES[$module]), 404);

        return view('admin.marketing-operations.enterprise-create', $this->enterpriseFormData($module));
    }

    public function enterpriseStore(Request $request, string $module, EnterpriseMarketingOperationsService $service)
    {
        $service->store($request, $module, $request->user());

        return redirect('/admin/marketing-operations/' . $module . '/operations')
            ->with('success', 'Marketing operations report submitted.');
    }

    public function enterpriseStatus(Request $request, string $module, int $id, EnterpriseMarketingOperationsService $service)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(EnterpriseMarketingOperationsService::STATUS_FLOW)],
        ]);
        $service->updateStatus($module, $id, $data['status'], $request->user());

        return back()->with('success', 'Marketing operations report status updated.');
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

    private function enterpriseFormData(string $module): array
    {
        return [
            'module' => $module,
            'moduleLabel' => EnterpriseMarketingOperationsService::MODULES[$module],
            'clients' => Client::orderBy('company_name')->get(),
            'pages' => ClientPage::with('client')->orderBy('page_name')->get(),
            'campaigns' => Campaign::with(['client', 'page'])->orderBy('campaign_name')->get(),
            'employees' => Employee::with(['departmentRecord'])->orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
        ];
    }
}
