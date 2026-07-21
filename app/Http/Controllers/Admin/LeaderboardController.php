<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Department;
use App\Services\PerformanceOperationsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index(Request $request, PerformanceOperationsService $service)
    {
        [$rows, $type, $from, $to, $filters, $metricLabels] = $this->rows($request, $service);
        $topRow = $rows->first();
        $summary = [
            'ranked_employees' => $rows->count(),
            'top_employee' => $topRow,
            'top_value' => $topRow['metric_value'] ?? 0,
            'metric_label' => $metricLabels[$type] ?? $metricLabels['orders'],
        ];

        return view('admin.leaderboard.index', compact('rows', 'type', 'from', 'to', 'filters', 'metricLabels', 'summary') + [
            'departments' => Department::ordered()->get(),
            'clients' => Client::orderBy('company_name')->get(),
        ]);
    }

    public function export(Request $request, PerformanceOperationsService $service)
    {
        [$rows, $type, , , , $metricLabels] = $this->rows($request, $service);

        return response()->streamDownload(function () use ($rows, $type, $metricLabels) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Rank', 'Employee', 'Role', 'Department', 'Metric', 'Value']);
            foreach ($rows as $index => $row) {
                fputcsv($handle, [$index + 1, $row['employee']->name, $row['employee']->roleName(), $row['employee']->departmentName(), $metricLabels[$type] ?? $type, $row['metric_value']]);
            }
            fclose($handle);
        }, 'employee-leaderboard.csv', ['Content-Type' => 'text/csv']);
    }

    private function rows(Request $request, PerformanceOperationsService $service): array
    {
        $filters = $request->validate([
            'type' => ['nullable', 'in:orders,approval,spend,cpo,consistency'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
        ]);
        $type = $filters['type'] ?? 'orders';
        $from = ! empty($filters['date_from']) ? Carbon::parse($filters['date_from']) : now()->startOfMonth();
        $to = ! empty($filters['date_to']) ? Carbon::parse($filters['date_to']) : now()->endOfMonth();
        $metricLabels = ['orders' => 'Confirmed Orders', 'approval' => 'Approval Rate', 'spend' => 'Approved Spend', 'cpo' => 'Cost Per Order', 'consistency' => 'Consistency'];
        $map = ['orders' => 'confirmed_orders', 'approval' => 'approval_rate', 'spend' => 'approved_spend', 'cpo' => 'average_cpo', 'consistency' => 'consistency'];
        $metric = $map[$type] ?? 'confirmed_orders';
        $rows = $service->kpiRows($from, $to, $filters)
            ->map(fn ($row) => $row + ['metric_value' => $row[$metric]])
            ->when($metric === 'average_cpo', fn ($rows) => $rows->where('metric_value', '>', 0))
            ->sortBy($metric === 'average_cpo' ? 'metric_value' : fn ($row) => -$row['metric_value'])
            ->values();

        return [$rows, $type, $from, $to, $filters, $metricLabels];
    }
}
