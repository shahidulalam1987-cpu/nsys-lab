<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Client;
use App\Services\SalaryMonthSheetService;
use Illuminate\Http\Request;

class SalaryMonthSheetController extends Controller
{
    public function index(Request $request, SalaryMonthSheetService $salaryMonthSheetService)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'payment_month' => ['nullable', 'date_format:Y-m'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'status' => ['nullable', 'in:generated,unpaid,partial,paid,final_settlement,reversed'],
            'salary_source' => ['nullable', 'in:' . implode(',', array_keys(Employee::SALARY_SOURCES))],
            'payment_source' => ['nullable', 'in:finance_ledger_linked,legacy_manual_paid,reversed,superseded'],
            'history_scope' => ['nullable', 'in:current,historical,all'],
        ]);

        $sheet = $salaryMonthSheetService->build($filters);

        $employees = Employee::orderBy('name')->get();

        return view('admin.salary-month-sheet.index', [
            'filters' => $filters,
            'month' => $sheet['month'],
            'employees' => $employees,
            'clients' => Client::orderBy('company_name')->get(),
            'rows' => $sheet['rows'],
            'summary' => $sheet['summary'],
            'integrity' => $sheet['integrity'],
            'historyScope' => $sheet['history_scope'],
        ]);
    }

    public function export(Request $request, SalaryMonthSheetService $salaryMonthSheetService)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'payment_month' => ['nullable', 'date_format:Y-m'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'status' => ['nullable', 'in:generated,unpaid,partial,paid,final_settlement,reversed'],
            'salary_source' => ['nullable', 'in:' . implode(',', array_keys(Employee::SALARY_SOURCES))],
            'payment_source' => ['nullable', 'in:finance_ledger_linked,legacy_manual_paid,reversed,superseded'],
            'history_scope' => ['nullable', 'in:current,historical,all'],
        ]);

        $sheet = $salaryMonthSheetService->build($filters);
        $fileName = 'employee-salary-report-' . ($sheet['month']?->format('Y-m') ?: 'all') . '.csv';

        return response()->streamDownload(function () use ($sheet) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Employee',
                'Client',
                'Salary Month',
                'Salary Period',
                'Working Days',
                'Payable Salary',
                'Paid Salary',
                'Remaining Due',
                'Status',
                'Payment Source Status',
                'Payment Date',
            ]);

            foreach ($sheet['rows'] as $payroll) {
                fputcsv($handle, [
                    trim(($payroll->employee?->employee_id ?: '-') . ' ' . ($payroll->employee?->name ?: '')),
                    $payroll->client?->company_name ?: '-',
                    $payroll->salary_month?->format('Y-m') ?: '-',
                    $payroll->salary_period,
                    $payroll->working_days ?? 0,
                    number_format($payroll->payable_salary, 2, '.', ''),
                    number_format($payroll->paid_amount, 2, '.', ''),
                    number_format(max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0), 2, '.', ''),
                    $payroll->reportStatusLabel(),
                    $payroll->paymentSourceStatusLabel(),
                    $payroll->payment_date?->toDateString() ?: '-',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportExcel(Request $request, SalaryMonthSheetService $salaryMonthSheetService)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'payment_month' => ['nullable', 'date_format:Y-m'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'status' => ['nullable', 'in:generated,unpaid,partial,paid,final_settlement,reversed'],
            'salary_source' => ['nullable', 'in:' . implode(',', array_keys(Employee::SALARY_SOURCES))],
            'payment_source' => ['nullable', 'in:finance_ledger_linked,legacy_manual_paid,reversed,superseded'],
            'history_scope' => ['nullable', 'in:current,historical,all'],
        ]);

        $sheet = $salaryMonthSheetService->build($filters);

        return response()->view('admin.salary-month-sheet.export-excel', [
            'rows' => $sheet['rows'],
        ], 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="employee-salary-report-' . ($sheet['month']?->format('Y-m') ?: 'all') . '.xls"',
        ]);
    }
}
