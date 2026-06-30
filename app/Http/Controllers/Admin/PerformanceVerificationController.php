<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDailySubmission;
use App\Models\PerformanceVerification;
use App\Services\PerformanceOperationsService;
use Illuminate\Http\Request;

class PerformanceVerificationController extends Controller
{
    public function index(Request $request, PerformanceOperationsService $service)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'string'],
        ]);
        $groups = $service->verificationGroups($filters)
            ->when($filters['status'] ?? null, fn ($rows, $status) => $rows->where('status', $status)->values());

        return view('admin.performance-verification.index', compact('groups', 'filters'));
    }

    public function markMismatch(Request $request, EmployeeDailySubmission $submission, PerformanceOperationsService $service)
    {
        $data = $request->validate(['admin_note' => ['required', 'string']]);
        PerformanceVerification::updateOrCreate([
            'group_key' => $service->groupKey($submission),
        ], [
            'performance_date' => $submission->submission_date,
            'client_id' => $submission->client_id,
            'page_id' => $submission->page_id,
            'campaign_id' => $submission->campaign_id,
            'status' => 'mismatch',
            'admin_note' => $data['admin_note'],
            'marked_by' => auth()->id(),
        ]);

        return back()->with('success', 'Performance group marked as mismatch.');
    }

    public function export(Request $request, PerformanceOperationsService $service)
    {
        $groups = $service->verificationGroups($request->only(['date_from', 'date_to']));

        return response()->streamDownload(function () use ($groups) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Client', 'Page', 'Campaign', 'Moderator', 'Orders', 'Ad Manager', 'Spend USD', 'CPO', 'BDT Spend', 'Profit', 'Margin %', 'Status']);
            foreach ($groups as $group) {
                fputcsv($handle, [
                    $group['date']?->toDateString(), $group['client']?->company_name, $group['page']?->page_name,
                    $group['campaign']?->campaign_name, $group['order']?->employee?->name, $group['calculation']['orders'],
                    $group['spend']?->employee?->name, $group['calculation']['spend'], $group['calculation']['cost_per_order'],
                    $group['calculation']['bdt_spend'], $group['calculation']['profit'], $group['calculation']['profit_margin'], $group['status'],
                ]);
            }
            fclose($handle);
        }, 'performance-verification.csv', ['Content-Type' => 'text/csv']);
    }
}
