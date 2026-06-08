<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;

class PortalController extends Controller
{
    public function salary()
    {
        $employee = auth()->user()
            ->employee()
            ->with('payrolls.client')
            ->firstOrFail();
        $payrolls = $employee->payrolls->sortByDesc('salary_month');

        return view('employee.salary', compact('employee', 'payrolls'));
    }

    public function assignments()
    {
        $employee = auth()->user()
            ->employee()
            ->with('assignments.client')
            ->firstOrFail();

        return view('employee.assignments', compact('employee'));
    }

    public function profile()
    {
        $employee = auth()->user()->employee()->firstOrFail();

        return view('employee.profile', compact('employee'));
    }

    public function notices()
    {
        return view('employee.notices');
    }
}
