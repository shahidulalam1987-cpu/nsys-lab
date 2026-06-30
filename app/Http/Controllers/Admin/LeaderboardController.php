<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PerformanceOperationsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index(Request $request, PerformanceOperationsService $service)
    {
        [$rows, $type, $from, $to] = $this->rows($request, $service);

        return view('admin.leaderboard.index', compact('rows', 'type', 'from', 'to'));
    }

    public function export(Request $request, PerformanceOperationsService $service)
    {
        [$rows, $type] = $this->rows($request, $service);

        return response()->streamDownload(function () use ($rows, $type) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Rank', 'Employee', 'Role', 'Department', 'Metric', 'Value']);
            foreach ($rows as $index => $row) {
                fputcsv($handle, [$index + 1, $row['employee']->name, $row['employee']->roleName(), $row['employee']->departmentName(), $type, $row['metric_value']]);
            }
            fclose($handle);
        }, 'employee-leaderboard.csv', ['Content-Type' => 'text/csv']);
    }

    private function rows(Request $request, PerformanceOperationsService $service): array
    {
        $type = $request->input('type', 'orders');
        $from = $request->filled('date_from') ? Carbon::parse($request->date_from) : now()->startOfMonth();
        $to = $request->filled('date_to') ? Carbon::parse($request->date_to) : now()->endOfMonth();
        $map = ['orders' => 'confirmed_orders', 'approval' => 'approval_rate', 'spend' => 'approved_spend', 'cpo' => 'average_cpo', 'consistency' => 'consistency'];
        $metric = $map[$type] ?? 'confirmed_orders';
        $rows = $service->kpiRows($from, $to, $request->only(['department_id', 'client_id']))
            ->map(fn ($row) => $row + ['metric_value' => $row[$metric]])
            ->when($metric === 'average_cpo', fn ($rows) => $rows->where('metric_value', '>', 0))
            ->sortBy($metric === 'average_cpo' ? 'metric_value' : fn ($row) => -$row['metric_value'])
            ->values();

        return [$rows, $type, $from, $to];
    }
}
