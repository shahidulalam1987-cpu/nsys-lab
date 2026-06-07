<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\SalaryMonthSheetService;
use Illuminate\Http\Request;

class SalaryMonthSheetController extends Controller
{
    public function index(Request $request, SalaryMonthSheetService $salaryMonthSheetService)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'status' => ['nullable', 'in:upcoming,unpaid,partial,paid'],
        ]);

        $sheet = $salaryMonthSheetService->build($filters);

        $employees = Employee::orderBy('name')->get();

        return view('admin.salary-month-sheet.index', [
            'filters' => $filters,
            'month' => $sheet['month'],
            'employees' => $employees,
            'rows' => $sheet['rows'],
            'summary' => $sheet['summary'],
        ]);
    }

    public function export(Request $request, SalaryMonthSheetService $salaryMonthSheetService)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'status' => ['nullable', 'in:upcoming,unpaid,partial,paid'],
        ]);

        $sheet = $salaryMonthSheetService->build($filters);
        $fileName = 'employee-salary-report-' . $sheet['month']->format('Y-m') . '.csv';

        return response()->streamDownload(function () use ($sheet) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Employee',
                'Client',
                'Salary Period',
                'Working Days',
                'Payable Salary',
                'Paid Salary',
                'Remaining Due',
                'Status',
                'Payment Date',
            ]);

            foreach ($sheet['rows'] as $payroll) {
                fputcsv($handle, [
                    trim(($payroll->employee?->employee_id ?: '-') . ' ' . ($payroll->employee?->name ?: '')),
                    $payroll->client?->company_name ?: '-',
                    $payroll->salary_period,
                    $payroll->working_days ?? 0,
                    number_format($payroll->payable_salary, 2, '.', ''),
                    number_format($payroll->paid_amount, 2, '.', ''),
                    number_format(max((float) $payroll->payable_salary - (float) $payroll->paid_amount, 0), 2, '.', ''),
                    ['upcoming' => 'Upcoming', 'unpaid' => 'Unpaid', 'partial' => 'Partially Paid', 'paid' => 'Paid'][$payroll->calculated_status] ?? ucfirst($payroll->calculated_status),
                    $payroll->payment_date?->toDateString() ?: '-',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
