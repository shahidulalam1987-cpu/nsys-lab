<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientPage;
use App\Models\DailyPerformanceReport;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeRole;
use App\Services\PerformanceOperationsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmployeeKpiController extends Controller
{
    public function index(Request $request, PerformanceOperationsService $service)
    {
        [$filters, $from, $to] = $this->filters($request);
        $rows = $service->kpiRows($from, $to, $filters);

        return view('admin.employee-kpi.index', $this->data($rows, $filters, $from, $to));
    }

    public function export(Request $request, PerformanceOperationsService $service)
    {
        [$filters, $from, $to] = $this->filters($request);
        $rows = $service->kpiRows($from, $to, $filters);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Employee', 'Department', 'Role', 'Submissions', 'Orders', 'Confirmed', 'Cancelled', 'Approved Spend', 'CPO', 'Approval %', 'Rejection %', 'Active Days', 'Missing Days', 'Target %', 'Profit Contribution']);
            foreach ($rows as $row) {
                fputcsv($handle, [$row['employee']->name, $row['employee']->departmentName(), $row['employee']->roleName(), $row['total_submissions'], $row['total_orders'], $row['confirmed_orders'], $row['cancelled_orders'], $row['approved_spend'], $row['average_cpo'], $row['approval_rate'], $row['rejection_rate'], $row['active_days'], $row['missing_days'], $row['target_achievement'], $row['profit_contribution']]);
            }
            fclose($handle);
        }, 'employee-kpi.csv', ['Content-Type' => 'text/csv']);
    }

    private function filters(Request $request): array
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'], 'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'employee_id' => ['nullable', 'exists:employees,id'], 'department_id' => ['nullable', 'exists:departments,id'],
            'role_id' => ['nullable', 'exists:employee_roles,id'], 'client_id' => ['nullable', 'exists:clients,id'],
            'page_id' => ['nullable', 'exists:client_pages,id'],
        ]);
        $from = ! empty($filters['date_from']) ? Carbon::parse($filters['date_from']) : now()->startOfMonth();
        $to = ! empty($filters['date_to']) ? Carbon::parse($filters['date_to']) : now()->endOfMonth();

        return [$filters, $from, $to];
    }

    private function data($rows, array $filters, Carbon $from, Carbon $to): array
    {
        return compact('rows', 'filters', 'from', 'to') + [
            'summary' => [
                'employees' => $rows->count(),
                'orders' => (int) $rows->sum('total_orders'),
                'confirmed_orders' => (int) $rows->sum('confirmed_orders'),
                'approved_spend' => (float) $rows->sum('approved_spend'),
                'average_cpo' => DailyPerformanceReport::costPer((float) $rows->sum('approved_spend'), (int) $rows->sum('confirmed_orders')),
                'approval_rate' => round((float) $rows->avg('approval_rate'), 2),
                'active_days' => (int) $rows->sum('active_days'),
                'top_employee' => $rows->sortByDesc('confirmed_orders')->first(),
            ],
            'employees' => Employee::orderBy('name')->get(), 'departments' => Department::ordered()->get(),
            'roles' => EmployeeRole::ordered()->get(), 'clients' => Client::orderBy('company_name')->get(),
            'pages' => ClientPage::orderBy('page_name')->get(),
        ];
    }
}
