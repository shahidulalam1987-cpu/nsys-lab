<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeePayroll;
use Barryvdh\DomPDF\Facade\Pdf;

class SalarySlipController extends Controller
{
    public function download(EmployeePayroll $payroll)
    {
        $employee = auth()->user()
            ->employee()
            ->with(['assignments.page', 'assignments.client'])
            ->firstOrFail();

        abort_unless($payroll->employee_id === $employee->id, 403);

        $payroll->load(['client']);
        $assignment = $employee->assignments
            ->where('client_id', $payroll->client_id)
            ->sortByDesc('assigned_from')
            ->first();
        $adjustments = collect($payroll->salary_day_adjustments ?? []);

        $pdf = Pdf::loadView('employee.pdf.salary-slip', [
            'employee' => $employee,
            'payroll' => $payroll,
            'assignment' => $assignment,
            'halfDays' => $adjustments->filter(fn ($adjustment) => (float) ($adjustment['salary_count_value'] ?? 0) === 0.5)->count(),
        ]);

        return $pdf->download('salary-slip-' . $payroll->id . '.pdf');
    }
}
