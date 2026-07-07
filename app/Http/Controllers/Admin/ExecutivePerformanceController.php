<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ExecutiveDashboardService;
use Illuminate\Http\Request;

class ExecutivePerformanceController extends Controller
{
    public function index(Request $request, ExecutiveDashboardService $dashboard)
    {
        $this->authorizeExecutiveAccess();

        return view('admin.executive-performance.index', [
            'dashboard' => $dashboard->build($request->only(['period', 'date_from', 'date_to'])),
        ]);
    }

    public function export(Request $request, string $format, ExecutiveDashboardService $dashboard)
    {
        $this->authorizeExecutiveAccess();

        $rows = $dashboard->exportRows($request->only(['period', 'date_from', 'date_to']));

        if ($format === 'excel') {
            return response()
                ->view('admin.executive-performance.export-excel', ['rows' => $rows])
                ->header('Content-Type', 'application/vnd.ms-excel')
                ->header('Content-Disposition', 'attachment; filename="executive-dashboard.xls"');
        }

        if ($format === 'pdf') {
            return response()
                ->view('admin.executive-performance.export-pdf', [
                    'dashboard' => $dashboard->build($request->only(['period', 'date_from', 'date_to'])),
                ])
                ->header('Content-Type', 'text/html');
        }

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Section', 'Metric', 'Value']);

            foreach ($rows as $row) {
                fputcsv($handle, [$row['section'], $row['metric'], $row['value']]);
            }

            fclose($handle);
        }, 'executive-dashboard.csv', ['Content-Type' => 'text/csv']);
    }

    private function authorizeExecutiveAccess(): void
    {
        $user = auth()->user();

        if ($user?->isSuperAdmin() || $user?->hasRole('agency_owner')) {
            return;
        }

        abort(403, 'Only Super Admin or Agency Owner can access Executive Dashboard.');
    }
}
