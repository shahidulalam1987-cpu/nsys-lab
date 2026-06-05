<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SalaryDay;
use Illuminate\Http\Request;

class SalaryDayController extends Controller
{
    public function store(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'date' => ['required', 'date'],
            'is_counted' => ['required', 'boolean'],
            'reason' => ['required', 'in:' . implode(',', SalaryDay::REASONS)],
            'note' => ['nullable', 'string'],
        ]);

        $employee->salaryDays()->updateOrCreate(
            [
                'client_id' => $data['client_id'],
                'date' => $data['date'],
            ],
            $data
        );

        return back()->with('success', 'Salary day saved successfully.');
    }
}
