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
        ]);

        $sheet = $salaryMonthSheetService->build($filters);
        $fileName = 'employee-salary-month-sheet-' . $sheet['month']->format('Y-m') . '.csv';

        return response()->streamDownload(function () use ($sheet) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Employee ID',
                'Employee Name',
                'Month',
                'Counted Days',
                'Non Counted Days',
                'Monthly Salary',
                'Payable Salary',
            ]);

            foreach ($sheet['rows'] as $row) {
                fputcsv($handle, [
                    $row['employee']->employee_id,
                    $row['employee']->name,
                    $row['month']->format('Y-m'),
                    $row['counted_days'],
                    $row['non_counted_days'],
                    number_format($row['monthly_salary'], 2, '.', ''),
                    number_format($row['payable_salary'], 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
