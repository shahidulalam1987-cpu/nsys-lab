<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Employee;
use App\Services\SalaryMonthSheetService;
use Illuminate\Http\Request;

class SalaryMonthSheetController extends Controller
{
    public function index(Request $request, SalaryMonthSheetService $salaryMonthSheetService)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'employee_id' => ['nullable', 'exists:employees,id'],
        ]);

        $sheet = $salaryMonthSheetService->build($filters);

        $clients = Client::orderBy('company_name')->get();
        $employees = Employee::orderBy('name')->get();

        return view('admin.salary-month-sheet.index', [
            'filters' => $filters,
            'month' => $sheet['month'],
            'clients' => $clients,
            'employees' => $employees,
            'rows' => $sheet['rows'],
            'summary' => $sheet['summary'],
        ]);
    }
}
