<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeePayroll;
use App\Services\SalaryStatementService;
use Barryvdh\DomPDF\Facade\Pdf;

class SalaryStatementController extends Controller
{
    public function download(EmployeePayroll $payroll, SalaryStatementService $salaryStatementService)
    {
        $data = $salaryStatementService->data($payroll);

        $pdf = Pdf::loadView('employee.pdf.salary-statement', $data)
            ->setPaper('a4');

        return $pdf->download('salary-statement-' . $data['reference'] . '.pdf');
    }
}
