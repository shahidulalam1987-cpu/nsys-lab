<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\PerformanceOperationsService;

class PerformanceController extends Controller
{
    public function index(PerformanceOperationsService $service)
    {
        $employee = auth()->user()->employee()->with(['departmentRecord', 'roleRecord'])->firstOrFail();
        $periods = [
            'today' => [today(), today()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
        ];
        $kpis = collect($periods)->map(fn ($range) => $service->employeeKpi($employee, $range[0], $range[1]));
        $bonusEarnings = $employee->bonusEarnings()->with('rule')->latest()->get();

        return view('employee.performance', compact('employee', 'kpis', 'bonusEarnings'));
    }
}
