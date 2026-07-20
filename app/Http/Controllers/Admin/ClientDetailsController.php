<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\DailyPerformanceReport;
use App\Models\SalaryPayment;
use App\Services\ClientFundDashboardService;

class ClientDetailsController extends Controller
{
    public function show(ClientFundDashboardService $clientFundDashboardService, $id)
    {
        $client = Client::findOrFail($id);
        $fundDetails = $clientFundDashboardService->clientDetails($client);
        $fundSummary = $fundDetails['row'];
        $fundLedger = $fundDetails['ledger'];

        $performanceReports = $client->dailyPerformanceReports()
            ->with(['campaign.page'])
            ->latest('report_date')
            ->latest()
            ->get();
        $payments = SalaryPayment::with(['financeAccount', 'clientFundLedgers'])
            ->where('client_id', $client->id)
            ->latest()
            ->get();
        $employeeAssignments = $client->employeeAssignments()
            ->with('employee')
            ->latest('assigned_from')
            ->get();
        $assignedEmployees = $employeeAssignments
            ->pluck('employee')
            ->filter()
            ->unique('id');
        $employeeSummary = [
            'total' => $assignedEmployees->count(),
            'active' => $assignedEmployees->where('status', 'active')->count(),
            'probation' => $assignedEmployees->where('status', 'probation')->count(),
            'on_leave' => $assignedEmployees->where('status', 'on_leave')->count(),
            'inactive' => $assignedEmployees->where('status', 'inactive')->count(),
            'terminated' => $assignedEmployees->where('status', 'terminated')->count(),
        ];
        $boostingPerformanceSummary = [
            'total_spend' => (float) $performanceReports->sum('spend'),
            'total_messages' => (int) $performanceReports->sum('messages'),
            'total_leads' => (int) $performanceReports->sum('leads'),
            'total_orders' => (int) $performanceReports->sum('orders'),
            'campaign_count' => $client->campaigns()->count(),
            'cpm' => DailyPerformanceReport::costPer((float) $performanceReports->sum('spend'), (int) $performanceReports->sum('messages')),
            'cpl' => DailyPerformanceReport::costPer((float) $performanceReports->sum('spend'), (int) $performanceReports->sum('leads')),
            'cpp' => DailyPerformanceReport::costPer((float) $performanceReports->sum('spend'), (int) $performanceReports->sum('orders')),
        ];

        return view('admin.clients.show', compact(
            'client',
            'fundSummary',
            'fundLedger',
            'performanceReports',
            'payments',
            'employeeAssignments',
            'employeeSummary',
            'boostingPerformanceSummary'
        ));
    }
}
